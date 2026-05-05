using System;
using System.Drawing;
using System.Threading.Tasks;
using System.Windows.Forms;
using ItMaster.Desktop.Infrastructure.Api;
using ItMaster.Desktop.Infrastructure.Storage;
using ItMaster.Desktop.Services;

namespace ItMaster.Desktop.Forms
{
    public class LoginForm : Form
    {
        private AuthService _authService;
        private ApiClient _apiClient;
        private readonly TokenStore _tokenStore;
        private readonly ApiSettingsStore _apiSettingsStore;
        private readonly TextBox _loginTextBox;
        private readonly TextBox _passwordTextBox;
        private readonly TextBox _apiUrlTextBox;
        private readonly Button _loginButton;
        private readonly Button _applyApiButton;
        private readonly Label _statusLabel;

        public LoginForm(AuthService authService, ApiClient apiClient)
            : this(authService, apiClient, new TokenStore(), new ApiSettingsStore())
        {
        }

        public LoginForm(AuthService authService, ApiClient apiClient, TokenStore tokenStore, ApiSettingsStore apiSettingsStore)
        {
            _authService = authService;
            _apiClient = apiClient;
            _tokenStore = tokenStore;
            _apiSettingsStore = apiSettingsStore;

            Text = "IT-Master Desktop - Вход";
            Width = 520;
            Height = 370;
            StartPosition = FormStartPosition.CenterScreen;
            FormBorderStyle = FormBorderStyle.FixedDialog;
            MaximizeBox = false;
            MinimizeBox = false;

            var apiLabel = new Label { Text = "API Base URL", Left = 24, Top = 24, Width = 200 };
            _apiUrlTextBox = new TextBox { Left = 24, Top = 46, Width = 350 };
            _applyApiButton = new Button { Left = 382, Top = 44, Width = 100, Height = 28, Text = "Применить" };
            _applyApiButton.Click += async delegate { await ApplyApiSettingsAsync(); };

            var loginLabel = new Label { Text = "Логин", Left = 24, Top = 88, Width = 350 };
            _loginTextBox = new TextBox { Left = 24, Top = 110, Width = 458 };

            var passwordLabel = new Label { Text = "Пароль", Left = 24, Top = 150, Width = 350 };
            _passwordTextBox = new TextBox
            {
                Left = 24,
                Top = 172,
                Width = 458,
                UseSystemPasswordChar = true,
            };

            _loginButton = new Button { Left = 24, Top = 218, Width = 458, Height = 34, Text = "Войти" };
            _loginButton.Click += async delegate { await LoginAsync(); };

            _statusLabel = new Label
            {
                Left = 24,
                Top = 262,
                Width = 458,
                Height = 56,
                ForeColor = Color.DarkRed,
            };

            Controls.Add(apiLabel);
            Controls.Add(_apiUrlTextBox);
            Controls.Add(_applyApiButton);
            Controls.Add(loginLabel);
            Controls.Add(_loginTextBox);
            Controls.Add(passwordLabel);
            Controls.Add(_passwordTextBox);
            Controls.Add(_loginButton);
            Controls.Add(_statusLabel);

            _apiUrlTextBox.Text = _apiClient.BaseUrl;
            _passwordTextBox.KeyDown += async delegate (object sender, KeyEventArgs e)
            {
                if (e.KeyCode == Keys.Enter)
                {
                    e.SuppressKeyPress = true;
                    await LoginAsync();
                }
            };

            Shown += async delegate
            {
                await TryRestoreSessionAsync();
            };
        }

        private async Task TryRestoreSessionAsync()
        {
            _statusLabel.Text = "Проверка сохраненной сессии...";
            var user = await _authService.TryRestoreSessionAsync();
            if (user == null)
            {
                _statusLabel.Text = string.Empty;
                return;
            }

            OpenMainForm(user);
        }

        private async Task LoginAsync()
        {
            if (string.IsNullOrWhiteSpace(_loginTextBox.Text) || string.IsNullOrWhiteSpace(_passwordTextBox.Text))
            {
                _statusLabel.Text = "Введите логин и пароль.";
                return;
            }

            _loginButton.Enabled = false;
            _statusLabel.Text = "Выполняется вход...";

            try
            {
                var user = await _authService.LoginAsync(_loginTextBox.Text.Trim(), _passwordTextBox.Text);
                OpenMainForm(user);
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
            finally
            {
                _loginButton.Enabled = true;
            }
        }

        private void OpenMainForm(Models.UserDto user)
        {
            Hide();
            using (var mainForm = new MainForm(_authService, _apiClient, user))
            {
                mainForm.ShowDialog(this);
            }

            Close();
        }

        private async Task ApplyApiSettingsAsync()
        {
            var value = (_apiUrlTextBox.Text ?? string.Empty).Trim();
            if (!Uri.TryCreate(value, UriKind.Absolute, out var uri)
                || (uri.Scheme != Uri.UriSchemeHttp && uri.Scheme != Uri.UriSchemeHttps))
            {
                _statusLabel.Text = "Введите корректный URL (http/https).";
                return;
            }

            _applyApiButton.Enabled = false;
            _loginButton.Enabled = false;
            _statusLabel.Text = "Применение API URL...";
            try
            {
                _apiSettingsStore.Save(uri.ToString());
                _tokenStore.Clear();

                _apiClient = new ApiClient(uri.ToString());
                _authService = new AuthService(_apiClient, _tokenStore);

                _statusLabel.Text = "API URL применен. Сохраненная сессия очищена.";
                await TryRestoreSessionAsync();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
            finally
            {
                _applyApiButton.Enabled = true;
                _loginButton.Enabled = true;
            }
        }
    }
}
