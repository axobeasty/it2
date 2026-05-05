using System;
using System.Drawing;
using System.Threading.Tasks;
using System.Windows.Forms;
using ItMaster.Desktop.Infrastructure.Api;

namespace ItMaster.Desktop.Forms
{
    public class SettingsControl : UserControl
    {
        private readonly ApiClient _apiClient;
        private readonly string _token;
        private readonly TextBox _titleBox;
        private readonly TextBox _reasonBox;
        private readonly CheckBox _enabledCheck;
        private readonly Label _statusLabel;
        private string _lastLoadedTitle = string.Empty;
        private string _lastLoadedReason = string.Empty;
        private bool _lastLoadedEnabled;

        public SettingsControl(ApiClient apiClient, string token)
        {
            _apiClient = apiClient;
            _token = token;

            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var title = new Label
            {
                Text = "Настройки (общие)",
                Left = 12,
                Top = 10,
                Width = 260,
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
            };

            var l1 = new Label { Left = 12, Top = 52, Width = 120, Text = "Название сайта" };
            _titleBox = new TextBox { Left = 12, Top = 72, Width = 620 };

            var l2 = new Label { Left = 12, Top = 106, Width = 180, Text = "Причина отключения" };
            _reasonBox = new TextBox { Left = 12, Top = 126, Width = 620, Height = 80, Multiline = true };

            _enabledCheck = new CheckBox { Left = 12, Top = 218, Width = 220, Text = "Сайт включен (is_enabled)" };

            var loadBtn = new Button { Left = 12, Top = 252, Width = 120, Height = 30, Text = "Загрузить" };
            loadBtn.Click += async delegate { await LoadSettingsAsync(); };
            var saveBtn = new Button { Left = 140, Top = 252, Width = 120, Height = 30, Text = "Сохранить" };
            saveBtn.Click += async delegate { await SaveSettingsAsync(); };
            var resetBtn = new Button { Left = 268, Top = 252, Width = 140, Height = 30, Text = "Сбросить изменения" };
            resetBtn.Click += delegate { ResetToLoaded(); };
            _titleBox.KeyDown += async delegate (object sender, KeyEventArgs e)
            {
                if (e.Control && e.KeyCode == Keys.S)
                {
                    e.SuppressKeyPress = true;
                    await SaveSettingsAsync();
                }
                if (e.KeyCode == Keys.Escape)
                {
                    e.SuppressKeyPress = true;
                    ResetToLoaded();
                }
            };
            _reasonBox.KeyDown += async delegate (object sender, KeyEventArgs e)
            {
                if (e.Control && e.KeyCode == Keys.S)
                {
                    e.SuppressKeyPress = true;
                    await SaveSettingsAsync();
                }
                if (e.KeyCode == Keys.Escape)
                {
                    e.SuppressKeyPress = true;
                    ResetToLoaded();
                }
            };

            _statusLabel = new Label { Left = 420, Top = 258, Width = 560, Height = 24 };

            Controls.Add(title);
            Controls.Add(l1);
            Controls.Add(_titleBox);
            Controls.Add(l2);
            Controls.Add(_reasonBox);
            Controls.Add(_enabledCheck);
            Controls.Add(loadBtn);
            Controls.Add(saveBtn);
            Controls.Add(resetBtn);
            Controls.Add(_statusLabel);

            Load += async delegate { await LoadSettingsAsync(); };
        }

        private async Task LoadSettingsAsync()
        {
            try
            {
                var response = await _apiClient.SettingsGeneralAsync(_token);
                _titleBox.Text = response?.Title ?? string.Empty;
                _reasonBox.Text = response?.DisableReason ?? string.Empty;
                _enabledCheck.Checked = response != null && response.IsEnabled;
                _lastLoadedTitle = _titleBox.Text;
                _lastLoadedReason = _reasonBox.Text;
                _lastLoadedEnabled = _enabledCheck.Checked;
                _statusLabel.Text = "Загружено: " + DateTime.Now.ToString("HH:mm:ss");
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task SaveSettingsAsync()
        {
            if (string.IsNullOrWhiteSpace(_titleBox.Text))
            {
                _statusLabel.Text = "Название не может быть пустым.";
                return;
            }

            try
            {
                var response = await _apiClient.SettingsGeneralSaveAsync(_token, _titleBox.Text.Trim(), _reasonBox.Text.Trim(), _enabledCheck.Checked);
                _lastLoadedTitle = _titleBox.Text.Trim();
                _lastLoadedReason = _reasonBox.Text.Trim();
                _lastLoadedEnabled = _enabledCheck.Checked;
                _statusLabel.Text = (response?.Message ?? "Сохранено.") + " (" + DateTime.Now.ToString("HH:mm:ss") + ")";
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private void ResetToLoaded()
        {
            _titleBox.Text = _lastLoadedTitle;
            _reasonBox.Text = _lastLoadedReason;
            _enabledCheck.Checked = _lastLoadedEnabled;
            _statusLabel.Text = "Изменения сброшены.";
        }
    }
}
