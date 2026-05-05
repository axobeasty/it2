using System.Windows;
using ItMaster.Desktop.Wpf.Infrastructure.Api;
using ItMaster.Desktop.Wpf.Infrastructure.Storage;
using ItMaster.Desktop.Wpf.Services;
using ItMaster.Desktop.Wpf.Views;

namespace ItMaster.Desktop.Wpf;

public partial class App : Application
{
    protected override void OnStartup(StartupEventArgs e)
    {
        base.OnStartup(e);

        var tokenStore = new TokenStore();
        var apiSettingsStore = new ApiSettingsStore();
        var apiBaseUrl = apiSettingsStore.Load("https://axobeast.ru/api/mobile/");
        var apiClient = new ApiClient(apiBaseUrl);
        var authService = new AuthService(apiClient, tokenStore);

        var login = new LoginWindow(authService, tokenStore, apiSettingsStore);
        login.Show();
    }
}

