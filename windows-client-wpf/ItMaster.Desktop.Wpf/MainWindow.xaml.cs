using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using System.Windows;
using ItMaster.Desktop.Wpf.Models;
using ItMaster.Desktop.Wpf.Services;
using ItMaster.Desktop.Wpf.Views;

namespace ItMaster.Desktop.Wpf;

public partial class MainWindow : Window
{
    private readonly AuthService _authService;
    private readonly UserDto _user;
    private readonly List<TestSummaryDto> _tests = new();
    private readonly Dictionary<int, object> _testAnswers = new();
    private TestDetailsDto _activeTest;
    private int _notificationsMaxId;
    private readonly List<NotificationItemDto> _notifications = new();
    private readonly List<WikiPageListItemDto> _wikiItems = new();
    private readonly List<OrderItemDto> _orders = new();
    private readonly List<InventoryItemDto> _inventory = new();
    private readonly Dictionary<string, bool> _permissions;
    private bool _disablePermissionFiltering;

    public MainWindow(AuthService authService, UserDto user)
    {
        InitializeComponent();
        _authService = authService;
        _user = user;
        _permissions = user?.Permissions ?? new Dictionary<string, bool>();
        _disablePermissionFiltering = DetectLegacyPermissionsPayload(user);

        HeaderTextBlock.Text = "Пользователь: " + (user?.FullName ?? user?.Login ?? "-") + " | Роль: " + (user?.RoleName ?? "-");
        AccessTextBlock.Text = _disablePermissionFiltering
            ? "Права API в legacy-режиме: фильтрация меню отключена."
            : "Права применяются по роли.";

        OrdersStatusSetCombo.ItemsSource = new[] { "Новая", "В работе", "Обработана", "Закрыта" };
        OrdersStatusSetCombo.SelectedIndex = 0;
        ScheduleWeekPicker.SelectedDate = DateTime.Today;

        Loaded += async (_, _) => await InitializeAsync();
    }

    private async Task InitializeAsync()
    {
        try
        {
            await LoadScheduleAsync();
            await LoadTestsAsync();
            await InitNotificationsAsync();
            await LoadTestStatsAsync();
            await LoadWikiAsync();
            await LoadOrdersMetaAndDataAsync();
            await LoadInventoryAsync();
            await LoadUsersRolesAsync();
            await LoadSettingsAsync();
            ApplyMenuPermissions();
            GlobalStatusTextBlock.Text = "Инициализация завершена.";
        }
        catch (Exception ex)
        {
            GlobalStatusTextBlock.Text = ex.Message;
        }
    }

    private void ApplyMenuPermissions()
    {
        if (_disablePermissionFiltering)
        {
            return;
        }
    }

    private async Task LoadScheduleAsync()
    {
        var monday = GetMonday(ScheduleWeekPicker.SelectedDate ?? DateTime.Today).ToString("yyyy-MM-dd");
        var response = await _authService.ApiClient.ScheduleAsync(_authService.GetActiveToken(), monday);
        ScheduleGrid.ItemsSource = response.Entries.Select(e => new
        {
            e.WeekdayLabel,
            Time = (e.StartTime ?? "") + "-" + (e.EndTime ?? ""),
            e.Subject,
            e.Teacher,
            e.Room,
            e.BuildingLabel,
        }).ToList();
    }

    private async Task LoadTestsAsync()
    {
        var response = await _authService.ApiClient.TestsListAsync(_authService.GetActiveToken());
        _tests.Clear();
        _tests.AddRange(response.Tests ?? new List<TestSummaryDto>());
        ApplyTestsFilter();
    }

    private void ApplyTestsFilter()
    {
        var term = (TestsSearchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
        var filtered = _tests.Where(t =>
            term == string.Empty
            || (t.Title ?? string.Empty).ToLowerInvariant().Contains(term)
            || (t.Description ?? string.Empty).ToLowerInvariant().Contains(term)
            || t.Id.ToString().Contains(term)).ToList();
        TestsGrid.ItemsSource = filtered.Select(t => new
        {
            t.Id,
            t.Title,
            Attempts = t.AttemptsUsed + "/" + t.AttemptsLimit,
            t.CanStart,
        }).ToList();
    }

    private async Task InitNotificationsAsync()
    {
        _notificationsMaxId = await _authService.ApiClient.NotificationsBootstrapAsync(_authService.GetActiveToken());
        await LoadNotificationsAsync();
    }

    private async Task LoadNotificationsAsync()
    {
        var response = await _authService.ApiClient.NotificationsAsync(_authService.GetActiveToken(), _notificationsMaxId);
        var incoming = response?.Items ?? new List<NotificationItemDto>();
        if (incoming.Count > 0)
        {
            _notifications.InsertRange(0, incoming);
            _notificationsMaxId = Math.Max(_notificationsMaxId, incoming.Max(i => i.Id));
        }

        var term = (NotificationsSearchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
        NotificationsGrid.ItemsSource = _notifications
            .Where(i => term == string.Empty
                || (i.Title ?? string.Empty).ToLowerInvariant().Contains(term)
                || (i.Message ?? string.Empty).ToLowerInvariant().Contains(term))
            .Select(i => new { i.Id, i.IsRead, i.Title, i.Message, i.CreatedAt })
            .ToList();
    }

    private async Task LoadTestStatsAsync()
    {
        var page = int.TryParse(StatsPageBox.Text, out var p) ? Math.Max(1, p) : 1;
        var group = StatsGroupCombo.SelectedItem is GroupOptionDto g ? g.Id : 0;
        var response = await _authService.ApiClient.TestStatsAsync(_authService.GetActiveToken(), group, page);
        StatsGroupCombo.ItemsSource = (response.Groups ?? new List<GroupOptionDto>()).Prepend(new GroupOptionDto { Id = 0, Name = "Все группы" }).ToList();
        if (StatsGroupCombo.SelectedIndex < 0) StatsGroupCombo.SelectedIndex = 0;
        StatsGrid.ItemsSource = (response.Attempts?.Data ?? new List<TestStatsAttemptRowDto>())
            .Select(a => new { a.StudentFullName, a.GroupName, a.TestTitle, Score = a.Score + "/" + a.MaxScore, a.Percentage, a.GradeLabel, a.SubmittedAt })
            .ToList();
    }

    private async Task LoadWikiAsync()
    {
        var response = await _authService.ApiClient.WikiListAsync(_authService.GetActiveToken());
        _wikiItems.Clear();
        _wikiItems.AddRange(response.Items ?? new List<WikiPageListItemDto>());
        ApplyWikiFilter();
    }

    private void ApplyWikiFilter()
    {
        var term = (WikiSearchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
        WikiGrid.ItemsSource = _wikiItems.Where(w => term == string.Empty
            || (w.Title ?? string.Empty).ToLowerInvariant().Contains(term)
            || (w.Slug ?? string.Empty).ToLowerInvariant().Contains(term))
            .Select(w => new { w.Title, w.Slug, w.UpdatedAt }).ToList();
    }

    private async Task LoadOrdersMetaAndDataAsync()
    {
        var categories = await _authService.ApiClient.OrdersCategoriesAsync(_authService.GetActiveToken());
        OrdersCategoryCombo.ItemsSource = categories.Items ?? new List<OrderCategoryDto>();
        OrdersCategoryCombo.DisplayMemberPath = "Name";
        OrdersCategoryCombo.SelectedValuePath = "Id";
        if (OrdersCategoryCombo.Items.Count > 0) OrdersCategoryCombo.SelectedIndex = 0;
        await LoadOrdersAsync();
    }

    private async Task LoadOrdersAsync()
    {
        var response = await _authService.ApiClient.OrdersMyAsync(_authService.GetActiveToken());
        _orders.Clear();
        _orders.AddRange(response.Items ?? new List<OrderItemDto>());
        OrdersGrid.ItemsSource = _orders.Select(o => new { o.Id, Status = OrderStatusText(o.Status), o.CategoryName, o.Description, o.Room, o.EmployeeFullName, o.CreatedAt }).ToList();
    }

    private async Task LoadInventoryAsync()
    {
        var canAdmin = HasAnyPermission("inventory_admin");
        var response = canAdmin
            ? await _authService.ApiClient.InventoryManageAsync(_authService.GetActiveToken())
            : await _authService.ApiClient.InventoryMyAsync(_authService.GetActiveToken());
        _inventory.Clear();
        _inventory.AddRange(response.Items ?? new List<InventoryItemDto>());
        ApplyInventoryFilter();
    }

    private void ApplyInventoryFilter()
    {
        var term = (InventorySearchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
        InventoryGrid.ItemsSource = _inventory.Where(x =>
            term == string.Empty
            || (x.EmployeeFullName ?? string.Empty).ToLowerInvariant().Contains(term)
            || (x.Name ?? string.Empty).ToLowerInvariant().Contains(term)
            || (x.InventoryNumber ?? string.Empty).ToLowerInvariant().Contains(term))
            .Select(x => new { x.EmployeeFullName, x.Name, x.InventoryNumber, x.Type, x.Room, x.DateIn }).ToList();
    }

    private async Task LoadUsersRolesAsync()
    {
        var token = _authService.GetActiveToken();
        var employees = await _authService.ApiClient.EmployeesAsync(token);
        var roles = await _authService.ApiClient.RolesAsync(token);
        var groups = await _authService.ApiClient.GroupsAsync(token);
        EmployeesGrid.ItemsSource = employees.Items ?? new List<EmployeeListItemDto>();
        RolesGrid.ItemsSource = roles.Items ?? new List<RoleListItemDto>();
        GroupsGrid.ItemsSource = groups.Items ?? new List<GroupListItemDto>();
    }

    private async Task LoadSettingsAsync()
    {
        var data = await _authService.ApiClient.SettingsGeneralAsync(_authService.GetActiveToken());
        SettingsTitleBox.Text = data.Title ?? string.Empty;
        SettingsReasonBox.Text = data.DisableReason ?? string.Empty;
        SettingsEnabledCheck.IsChecked = data.IsEnabled;
    }

    private async void ScheduleRefresh_OnClick(object sender, RoutedEventArgs e) => await SafeRun(LoadScheduleAsync);
    private async void TestsRefresh_OnClick(object sender, RoutedEventArgs e) => await SafeRun(LoadTestsAsync);
    private async void StatsLoad_OnClick(object sender, RoutedEventArgs e) => await SafeRun(LoadTestStatsAsync);
    private async void NotificationsRefresh_OnClick(object sender, RoutedEventArgs e) => await SafeRun(LoadNotificationsAsync);
    private async void WikiRefresh_OnClick(object sender, RoutedEventArgs e) => await SafeRun(LoadWikiAsync);
    private async void OrdersRefresh_OnClick(object sender, RoutedEventArgs e) => await SafeRun(LoadOrdersAsync);
    private async void InventoryRefresh_OnClick(object sender, RoutedEventArgs e) => await SafeRun(LoadInventoryAsync);
    private async void UsersRolesRefresh_OnClick(object sender, RoutedEventArgs e) => await SafeRun(LoadUsersRolesAsync);
    private async void SettingsLoad_OnClick(object sender, RoutedEventArgs e) => await SafeRun(LoadSettingsAsync);

    private async void TestsStart_OnClick(object sender, RoutedEventArgs e)
    {
        var id = GetSelectedIntFromGrid(TestsGrid);
        if (id == null)
        {
            GlobalStatusTextBlock.Text = "Выберите тест.";
            return;
        }

        await SafeRun(async () =>
        {
            var session = await _authService.ApiClient.TestBeginAsync(_authService.GetActiveToken(), id.Value);
            _activeTest = session.Test;
            _testAnswers.Clear();
            TestQuestionsGrid.ItemsSource = (_activeTest.Questions ?? new List<TestQuestionDto>()).Select(q => new { q.Id, q.Type, q.QuestionText }).ToList();
            GlobalStatusTextBlock.Text = "Тест запущен: " + _activeTest.Title;
        });
    }

    private async void TestsSubmit_OnClick(object sender, RoutedEventArgs e)
    {
        if (_activeTest == null)
        {
            GlobalStatusTextBlock.Text = "Сначала запустите тест.";
            return;
        }

        await SafeRun(async () =>
        {
            var payload = "{}";
            var result = await _authService.ApiClient.TestSubmitAsync(_authService.GetActiveToken(), _activeTest.Id, payload);
            GlobalStatusTextBlock.Text = (result.Message ?? "Отправлено.") + " Оценка: " + (result.Attempt?.GradeLabel ?? "-");
            await LoadTestsAsync();
        });
    }

    private async void OrdersCreate_OnClick(object sender, RoutedEventArgs e)
    {
        if (OrdersCategoryCombo.SelectedItem is not OrderCategoryDto category)
        {
            GlobalStatusTextBlock.Text = "Выберите категорию.";
            return;
        }
        if (string.IsNullOrWhiteSpace(OrdersDescriptionBox.Text))
        {
            GlobalStatusTextBlock.Text = "Введите описание.";
            return;
        }

        await SafeRun(async () =>
        {
            var response = await _authService.ApiClient.OrdersCreateAsync(_authService.GetActiveToken(), OrdersDescriptionBox.Text.Trim(), category.Id, OrdersRoomBox.Text?.Trim() ?? string.Empty);
            GlobalStatusTextBlock.Text = response.Message;
            OrdersDescriptionBox.Text = string.Empty;
            OrdersRoomBox.Text = string.Empty;
            await LoadOrdersAsync();
        });
    }

    private async void OrdersSetStatus_OnClick(object sender, RoutedEventArgs e)
    {
        var id = GetSelectedIntFromGrid(OrdersGrid);
        if (id == null)
        {
            GlobalStatusTextBlock.Text = "Выберите заявку.";
            return;
        }

        var status = OrdersStatusSetCombo.SelectedIndex;
        await SafeRun(async () =>
        {
            var response = await _authService.ApiClient.OrdersSetStatusAsync(_authService.GetActiveToken(), id.Value, status);
            GlobalStatusTextBlock.Text = response.Message ?? "Статус обновлен.";
            await LoadOrdersAsync();
        });
    }

    private async void SettingsSave_OnClick(object sender, RoutedEventArgs e)
    {
        await SafeRun(async () =>
        {
            var response = await _authService.ApiClient.SettingsGeneralSaveAsync(
                _authService.GetActiveToken(),
                SettingsTitleBox.Text?.Trim() ?? string.Empty,
                SettingsReasonBox.Text?.Trim() ?? string.Empty,
                SettingsEnabledCheck.IsChecked == true);
            GlobalStatusTextBlock.Text = response.Message ?? "Сохранено.";
        });
    }

    private async void WikiGrid_OnSelectionChanged(object sender, System.Windows.Controls.SelectionChangedEventArgs e)
    {
        var slug = GetSelectedStringFromGrid(WikiGrid, "Slug");
        if (string.IsNullOrWhiteSpace(slug))
        {
            return;
        }

        await SafeRun(async () =>
        {
            var response = await _authService.ApiClient.WikiShowAsync(_authService.GetActiveToken(), slug);
            WikiBodyBox.Text = response.Page?.Body ?? string.Empty;
        });
    }

    private void TestsGrid_OnSelectionChanged(object sender, System.Windows.Controls.SelectionChangedEventArgs e)
    {
        // зарезервировано под расширенный editor ответов
    }

    private void TestsSearchBox_OnTextChanged(object sender, System.Windows.Controls.TextChangedEventArgs e) => ApplyTestsFilter();
    private void NotificationsSearchBox_OnTextChanged(object sender, System.Windows.Controls.TextChangedEventArgs e) => _ = LoadNotificationsAsync();
    private void InventorySearchBox_OnTextChanged(object sender, System.Windows.Controls.TextChangedEventArgs e) => ApplyInventoryFilter();
    private void WikiSearchBox_OnTextChanged(object sender, System.Windows.Controls.TextChangedEventArgs e) => ApplyWikiFilter();

    private void OpenSchedule_OnClick(object sender, RoutedEventArgs e) => ModulesTabControl.SelectedIndex = 0;
    private void OpenTests_OnClick(object sender, RoutedEventArgs e) => ModulesTabControl.SelectedIndex = 1;
    private void OpenTestStats_OnClick(object sender, RoutedEventArgs e) => ModulesTabControl.SelectedIndex = 2;
    private void OpenNotifications_OnClick(object sender, RoutedEventArgs e) => ModulesTabControl.SelectedIndex = 3;
    private void OpenOrders_OnClick(object sender, RoutedEventArgs e) => ModulesTabControl.SelectedIndex = 4;
    private void OpenInventory_OnClick(object sender, RoutedEventArgs e) => ModulesTabControl.SelectedIndex = 5;
    private void OpenWiki_OnClick(object sender, RoutedEventArgs e) => ModulesTabControl.SelectedIndex = 6;
    private void OpenUsersRoles_OnClick(object sender, RoutedEventArgs e) => ModulesTabControl.SelectedIndex = 7;
    private void OpenSettings_OnClick(object sender, RoutedEventArgs e) => ModulesTabControl.SelectedIndex = 8;

    private void Logout_OnClick(object sender, RoutedEventArgs e)
    {
        _authService.Logout();
        var login = new LoginWindow(_authService, new Infrastructure.Storage.TokenStore(), new Infrastructure.Storage.ApiSettingsStore());
        login.Show();
        Close();
    }

    private async Task SafeRun(Func<Task> action)
    {
        try
        {
            await action();
        }
        catch (Exception ex)
        {
            GlobalStatusTextBlock.Text = ex.Message;
        }
    }

    private bool HasAnyPermission(params string[] keys)
    {
        if (_disablePermissionFiltering)
        {
            return true;
        }
        return keys.Any(k => _permissions.TryGetValue(k, out var allowed) && allowed);
    }

    private static bool DetectLegacyPermissionsPayload(UserDto user)
    {
        var permissions = user?.Permissions;
        if (permissions == null || permissions.Count == 0)
        {
            return true;
        }

        if (permissions.Count <= 4
            && permissions.ContainsKey("schedule_my")
            && permissions.ContainsKey("student_tests")
            && permissions.ContainsKey("tests_admin")
            && permissions.ContainsKey("tests_stats"))
        {
            return true;
        }

        var roleName = user?.RoleName ?? string.Empty;
        var isAdmin = roleName.Contains("администратор", StringComparison.OrdinalIgnoreCase)
                      || roleName.Contains("admin", StringComparison.OrdinalIgnoreCase);
        if (isAdmin)
        {
            var hasAdminKeys = permissions.ContainsKey("employees_manage")
                || permissions.ContainsKey("roles_manage")
                || permissions.ContainsKey("groups_manage")
                || permissions.ContainsKey("settings");
            if (!hasAdminKeys)
            {
                return true;
            }
        }

        return false;
    }

    private static int? GetSelectedIntFromGrid(System.Windows.Controls.DataGrid grid)
    {
        if (grid.SelectedItem == null)
        {
            return null;
        }
        var prop = grid.SelectedItem.GetType().GetProperty("Id");
        if (prop == null)
        {
            return null;
        }
        var value = prop.GetValue(grid.SelectedItem);
        return value is int id ? id : null;
    }

    private static string GetSelectedStringFromGrid(System.Windows.Controls.DataGrid grid, string propertyName)
    {
        if (grid.SelectedItem == null)
        {
            return string.Empty;
        }
        var prop = grid.SelectedItem.GetType().GetProperty(propertyName);
        if (prop == null)
        {
            return string.Empty;
        }
        return Convert.ToString(prop.GetValue(grid.SelectedItem)) ?? string.Empty;
    }

    private static DateTime GetMonday(DateTime date)
    {
        var diff = (7 + (date.DayOfWeek - DayOfWeek.Monday)) % 7;
        return date.AddDays(-diff).Date;
    }

    private static string OrderStatusText(int status) => status switch
    {
        0 => "Новая",
        1 => "В работе",
        2 => "Обработана",
        3 => "Закрыта",
        _ => status.ToString(),
    };
}