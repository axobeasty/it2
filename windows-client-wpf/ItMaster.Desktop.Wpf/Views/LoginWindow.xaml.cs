using System;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Input;
using ItMaster.Desktop.Wpf.Infrastructure.Api;
using ItMaster.Desktop.Wpf.Infrastructure.Storage;
using ItMaster.Desktop.Wpf.Services;

namespace ItMaster.Desktop.Wpf.Views;

public partial class LoginWindow : Window
{
    private AuthService _authService;
    private readonly TokenStore _tokenStore;
    private readonly ApiSettingsStore _apiSettingsStore;

    public LoginWindow(AuthService authService, TokenStore tokenStore, ApiSettingsStore apiSettingsStore)
    {
        InitializeComponent();
        _authService = authService;
        _tokenStore = tokenStore;
        _apiSettingsStore = apiSettingsStore;
        ApiUrlTextBox.Text = _authService.ApiClient.BaseUrl;
        Loaded += async (_, _) => await TryRestoreSessionAsync();
    }

    private async Task TryRestoreSessionAsync()
    {
        StatusTextBlock.Text = "Проверка сохраненной сессии...";
        var user = await _authService.TryRestoreSessionAsync();
        if (user == null)
        {
            StatusTextBlock.Text = string.Empty;
            return;
        }

        OpenMain(user);
    }

    private async void LoginButton_OnClick(object sender, RoutedEventArgs e) => await LoginAsync();

    private async Task LoginAsync()
    {
        if (string.IsNullOrWhiteSpace(LoginTextBox.Text) || string.IsNullOrWhiteSpace(PasswordTextBox.Password))
        {
            StatusTextBlock.Text = "Введите логин и пароль.";
            return;
        }

        LoginButton.IsEnabled = false;
        StatusTextBlock.Text = "Выполняется вход...";
        try
        {
            var user = await _authService.LoginAsync(LoginTextBox.Text.Trim(), PasswordTextBox.Password);
            OpenMain(user);
        }
        catch (Exception ex)
        {
            StatusTextBlock.Text = ex.Message;
        }
        finally
        {
            LoginButton.IsEnabled = true;
        }
    }

    private void OpenMain(Models.UserDto user)
    {
        var main = new MainWindow(_authService, user);
        main.Show();
        Close();
    }

    private async void ApplyApiButton_OnClick(object sender, RoutedEventArgs e)
    {
        var value = (ApiUrlTextBox.Text ?? string.Empty).Trim();
        if (!Uri.TryCreate(value, UriKind.Absolute, out var uri)
            || (uri.Scheme != Uri.UriSchemeHttp && uri.Scheme != Uri.UriSchemeHttps))
        {
            StatusTextBlock.Text = "Введите корректный URL (http/https).";
            return;
        }

        ApplyApiButton.IsEnabled = false;
        LoginButton.IsEnabled = false;
        try
        {
            _apiSettingsStore.Save(uri.ToString());
            _tokenStore.Clear();
            var client = new ApiClient(uri.ToString());
            _authService = new AuthService(client, _tokenStore);
            StatusTextBlock.Text = "API URL применен. Сессия очищена.";
            await TryRestoreSessionAsync();
        }
        catch (Exception ex)
        {
            StatusTextBlock.Text = ex.Message;
        }
        finally
        {
            ApplyApiButton.IsEnabled = true;
            LoginButton.IsEnabled = true;
        }
    }

    private async void PasswordTextBox_OnKeyDown(object sender, KeyEventArgs e)
    {
        if (e.Key == Key.Enter)
        {
            e.Handled = true;
            await LoginAsync();
        }
    }
}
