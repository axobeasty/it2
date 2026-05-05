using System;
using System.Configuration;
using System.Windows.Forms;
using ItMaster.Desktop.Forms;
using ItMaster.Desktop.Infrastructure.Api;
using ItMaster.Desktop.Infrastructure.Storage;
using ItMaster.Desktop.Services;

namespace ItMaster.Desktop
{
    internal static class Program
    {
        [STAThread]
        private static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);

            var defaultBaseUrl = ConfigurationManager.AppSettings["ApiBaseUrl"] ?? "https://axobeast.ru/api/mobile/";
            var apiSettingsStore = new ApiSettingsStore();
            var baseUrl = apiSettingsStore.Load(defaultBaseUrl);
            var tokenStore = new TokenStore();
            var apiClient = new ApiClient(baseUrl);
            var authService = new AuthService(apiClient, tokenStore);

            Application.Run(new LoginForm(authService, apiClient, tokenStore, apiSettingsStore));
        }
    }
}
