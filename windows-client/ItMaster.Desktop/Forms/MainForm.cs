using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Windows.Forms;
using ItMaster.Desktop.Infrastructure.Api;
using ItMaster.Desktop.Models;
using ItMaster.Desktop.Services;

namespace ItMaster.Desktop.Forms
{
    public class MainForm : Form
    {
        private readonly AuthService _authService;
        private readonly ApiClient _apiClient;
        private readonly UserDto _user;
        private readonly Panel _contentPanel;
        private readonly Label _accessLabel;

        public MainForm(AuthService authService, ApiClient apiClient, UserDto user)
        {
            _authService = authService;
            _apiClient = apiClient;
            _user = user;

            Text = "IT-Master Desktop";
            Width = 1200;
            Height = 760;
            StartPosition = FormStartPosition.CenterScreen;

            var sideMenu = new FlowLayoutPanel
            {
                Dock = DockStyle.Left,
                Width = 240,
                FlowDirection = FlowDirection.TopDown,
                WrapContents = false,
                AutoScroll = true,
                BackColor = Color.FromArgb(245, 247, 250),
                Padding = new Padding(8),
            };

            _contentPanel = new Panel { Dock = DockStyle.Fill, BackColor = Color.White };

            var header = new Label
            {
                Text = "Пользователь: " + (_user.FullName ?? _user.Login) + " | Роль: " + (_user.RoleName ?? "-"),
                Dock = DockStyle.Top,
                Height = 42,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                Padding = new Padding(10, 10, 0, 0),
            };

            _accessLabel = new Label
            {
                Dock = DockStyle.Top,
                Height = 24,
                Font = new Font("Segoe UI", 8),
                ForeColor = Color.DimGray,
                Padding = new Padding(10, 2, 0, 0),
            };

            Controls.Add(_contentPanel);
            Controls.Add(sideMenu);
            Controls.Add(_accessLabel);
            Controls.Add(header);

            BuildMenu(sideMenu);
            OpenModule(
                "Добро пожаловать",
                "Это desktop-клиент IT-Master. Далее сюда переносится бизнес-функционал сайта модуль за модулем."
            );
        }

        private void BuildMenu(FlowLayoutPanel menu)
        {
            var modules = new List<MenuModule>
            {
                new MenuModule("Расписание", "Модуль просмотра и управления расписанием.", "schedule_my"),
                new MenuModule("Тесты", "Список тестов, прохождение, администрирование, статистика.", "student_tests"),
                new MenuModule("Статистика тестов", "Сводная статистика прохождения тестов.", "tests_stats"),
                new MenuModule("Уведомления", "Центр уведомлений и событий.", null),
                new MenuModule("Заявки", "Создание и управление заявками.", "orders_my"),
                new MenuModule("Инвентарь", "Учет, выдача и движение инвентаря.", "inventory_my"),
                new MenuModule("Wiki", "База знаний, просмотр и редактирование.", "knowledge_wiki"),
                new MenuModule("Пользователи и роли", "Управление сотрудниками, ролями, группами.", "employees_manage"),
                new MenuModule("Настройки", "Системные параметры и сервисные функции.", "settings"),
            };

            var hiddenModules = new List<string>();

            foreach (var module in modules)
            {
                if (!CanOpenModule(module))
                {
                    hiddenModules.Add(module.Title);
                    continue;
                }

                var title = module.Title;
                var description = module.Description;
                var button = new Button
                {
                    Width = 210,
                    Height = 36,
                    Text = title,
                    Margin = new Padding(3, 3, 3, 6),
                };

                button.Click += delegate
                {
                    if (title == "Расписание")
                    {
                        OpenSchedule();
                        return;
                    }
                    if (title == "Тесты")
                    {
                        OpenTests();
                        return;
                    }
                    if (title == "Уведомления")
                    {
                        OpenNotifications();
                        return;
                    }
                    if (title == "Статистика тестов")
                    {
                        OpenTestStats();
                        return;
                    }
                    if (title == "Wiki")
                    {
                        OpenWiki();
                        return;
                    }
                    if (title == "Заявки")
                    {
                        OpenOrders();
                        return;
                    }
                    if (title == "Инвентарь")
                    {
                        OpenInventory();
                        return;
                    }
                    if (title == "Пользователи и роли")
                    {
                        OpenUsersRoles();
                        return;
                    }
                    if (title == "Настройки")
                    {
                        OpenSettings();
                        return;
                    }

                    OpenModule(title, description);
                };
                menu.Controls.Add(button);
            }

            _accessLabel.Text = hiddenModules.Count == 0
                ? "Доступ: все модули меню активны."
                : "Скрыты по правам: " + string.Join(", ", hiddenModules.Take(4)) + (hiddenModules.Count > 4 ? "..." : string.Empty);

            var logoutButton = new Button
            {
                Width = 210,
                Height = 36,
                Text = "Выйти",
                BackColor = Color.FromArgb(225, 86, 86),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Margin = new Padding(3, 12, 3, 6),
            };
            logoutButton.Click += LogoutButton_Click;
            menu.Controls.Add(logoutButton);
        }

        private void OpenModule(string title, string description)
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new ModulePlaceholderControl(title, description));
        }

        private void OpenSchedule()
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new ScheduleControl(_apiClient, _authService.GetActiveToken()));
        }

        private void OpenTests()
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new TestsControl(_apiClient, _authService.GetActiveToken()));
        }

        private void OpenNotifications()
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new NotificationsControl(_apiClient, _authService.GetActiveToken()));
        }

        private void OpenTestStats()
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new TestStatsControl(_apiClient, _authService.GetActiveToken()));
        }

        private void OpenWiki()
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new WikiControl(_apiClient, _authService.GetActiveToken()));
        }

        private void OpenOrders()
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new OrdersControl(_apiClient, _authService.GetActiveToken(), HasAnyPermission("orders_admin")));
        }

        private void OpenInventory()
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new InventoryControl(_apiClient, _authService.GetActiveToken(), HasAnyPermission("inventory_admin")));
        }

        private void OpenUsersRoles()
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new UsersRolesControl(_apiClient, _authService.GetActiveToken()));
        }

        private void OpenSettings()
        {
            _contentPanel.Controls.Clear();
            _contentPanel.Controls.Add(new SettingsControl(_apiClient, _authService.GetActiveToken()));
        }

        private void LogoutButton_Click(object sender, EventArgs e)
        {
            _authService.Logout();
            Hide();
            using (var login = new LoginForm(_authService, _apiClient))
            {
                login.ShowDialog();
            }

            Close();
        }

        private bool CanOpenModule(MenuModule module)
        {
            if (string.IsNullOrWhiteSpace(module.PermissionKey))
            {
                return true;
            }

            if (_user.Permissions == null)
            {
                return false;
            }

            if (module.Title == "Тесты")
            {
                return HasAnyPermission("student_tests", "tests_admin");
            }
            if (module.Title == "Статистика тестов")
            {
                return HasAnyPermission("tests_stats", "tests_admin");
            }
            if (module.Title == "Заявки")
            {
                return HasAnyPermission("orders_my", "orders_admin");
            }
            if (module.Title == "Инвентарь")
            {
                return HasAnyPermission("inventory_my", "inventory_admin");
            }
            if (module.Title == "Пользователи и роли")
            {
                return HasAnyPermission("employees_manage", "roles_manage", "groups_manage");
            }

            bool allowed;
            if (_user.Permissions.TryGetValue(module.PermissionKey, out allowed))
            {
                return allowed;
            }

            return false;
        }

        private bool HasAnyPermission(params string[] keys)
        {
            if (_user.Permissions == null || keys == null)
            {
                return false;
            }

            foreach (var key in keys)
            {
                bool allowed;
                if (_user.Permissions.TryGetValue(key, out allowed) && allowed)
                {
                    return true;
                }
            }

            return false;
        }

        private class MenuModule
        {
            public MenuModule(string title, string description, string permissionKey)
            {
                Title = title;
                Description = description;
                PermissionKey = permissionKey;
            }

            public string Title { get; private set; }
            public string Description { get; private set; }
            public string PermissionKey { get; private set; }
        }
    }
}
