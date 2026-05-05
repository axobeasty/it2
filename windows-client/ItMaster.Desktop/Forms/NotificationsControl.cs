using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;
using ItMaster.Desktop.Infrastructure.Api;
using ItMaster.Desktop.Infrastructure.Ui;
using ItMaster.Desktop.Models;

namespace ItMaster.Desktop.Forms
{
    public class NotificationsControl : UserControl
    {
        private readonly ApiClient _apiClient;
        private readonly string _token;
        private readonly Label _statusLabel;
        private readonly DataGridView _grid;
        private readonly TextBox _searchBox;
        private readonly Timer _timer;
        private readonly List<NotificationItemDto> _items = new List<NotificationItemDto>();
        private int _maxId;

        public NotificationsControl(ApiClient apiClient, string token)
        {
            _apiClient = apiClient;
            _token = token;

            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var top = new Panel { Dock = DockStyle.Top, Height = 80 };
            var title = new Label
            {
                Text = "Уведомления",
                Left = 12,
                Top = 10,
                Width = 220,
                Font = new Font("Segoe UI", 13, FontStyle.Bold),
            };
            var refreshButton = new Button
            {
                Left = 240,
                Top = 10,
                Width = 110,
                Height = 28,
                Text = "Обновить",
            };
            refreshButton.Click += async delegate { await PullNotificationsAsync(); };
            var clearButton = new Button
            {
                Left = 360,
                Top = 10,
                Width = 150,
                Height = 28,
                Text = "Очистить список",
            };
            clearButton.Click += delegate
            {
                _items.Clear();
                Bind();
                _statusLabel.Text = "Список очищен.";
            };
            _searchBox = new TextBox { Left = 12, Top = 44, Width = 338 };
            _searchBox.TextChanged += delegate { Bind(); };

            _statusLabel = new Label { Left = 520, Top = 14, Width = 600, Height = 24 };

            top.Controls.Add(title);
            top.Controls.Add(refreshButton);
            top.Controls.Add(clearButton);
            top.Controls.Add(_searchBox);
            top.Controls.Add(_statusLabel);

            _grid = new DataGridView
            {
                Dock = DockStyle.Fill,
                ReadOnly = true,
                AutoGenerateColumns = false,
                AllowUserToAddRows = false,
                AllowUserToDeleteRows = false,
                SelectionMode = DataGridViewSelectionMode.FullRowSelect,
                MultiSelect = true,
            };
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "ID", DataPropertyName = "Id", Width = 60 });
            _grid.Columns.Add(new DataGridViewCheckBoxColumn { HeaderText = "Прочитано", DataPropertyName = "IsRead", Width = 90 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Заголовок", DataPropertyName = "Title", Width = 280 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Сообщение", DataPropertyName = "Message", Width = 520 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Создано", DataPropertyName = "CreatedAt", Width = 180 });
            _grid.KeyDown += delegate (object sender, KeyEventArgs e)
            {
                if (e.Control && e.KeyCode == Keys.C)
                {
                    e.SuppressKeyPress = true;
                    CopySelectedNotifications();
                }
            };
            var copyButton = new Button
            {
                Left = 520,
                Top = 44,
                Width = 180,
                Height = 28,
                Text = "Копировать выделенные",
            };
            copyButton.Click += delegate { CopySelectedNotifications(); };
            top.Controls.Add(copyButton);

            Controls.Add(_grid);
            Controls.Add(top);

            _timer = new Timer { Interval = 10000 };
            _timer.Tick += async delegate { await PullNotificationsAsync(); };

            Load += async delegate
            {
                await InitAsync();
                _timer.Start();
            };
            Disposed += delegate { _timer.Stop(); };
        }

        private async Task InitAsync()
        {
            if (string.IsNullOrWhiteSpace(_token))
            {
                _statusLabel.Text = "Сессия не найдена. Выполните вход заново.";
                return;
            }

            try
            {
                _statusLabel.Text = "Инициализация уведомлений...";
                _maxId = await _apiClient.NotificationsBootstrapAsync(_token);
                _statusLabel.Text = "Начальная синхронизация выполнена. Последний ID: " + _maxId;
                Bind();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task PullNotificationsAsync()
        {
            if (string.IsNullOrWhiteSpace(_token))
            {
                return;
            }

            try
            {
                var response = await _apiClient.NotificationsAsync(_token, _maxId);
                var incoming = response?.Items ?? new List<NotificationItemDto>();
                if (incoming.Count > 0)
                {
                    _items.InsertRange(0, incoming);
                    _items.Sort((a, b) => b.Id.CompareTo(a.Id));
                    _maxId = Math.Max(_maxId, incoming.Max(x => x.Id));
                    Bind();
                }

                _statusLabel.Text = "Уведомлений: " + _items.Count + ", последний ID: " + _maxId + ", обновлено: " + DateTime.Now.ToString("HH:mm:ss");
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private void Bind()
        {
            var term = (_searchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
            _grid.DataSource = new BindingSource
            {
                DataSource = _items
                .Where(i =>
                    term == string.Empty
                    || (i.Title ?? string.Empty).ToLowerInvariant().Contains(term)
                    || (i.Message ?? string.Empty).ToLowerInvariant().Contains(term)
                    || i.Id.ToString().Contains(term))
                .Select(i => new
                {
                    i.Id,
                    i.IsRead,
                    Title = i.Title ?? string.Empty,
                    Message = i.Message ?? string.Empty,
                    CreatedAt = i.CreatedAt ?? string.Empty,
                }).ToList()
            };
        }

        private void CopySelectedNotifications()
        {
            var lines = new List<string>();
            foreach (DataGridViewRow row in _grid.SelectedRows)
            {
                if (row == null || row.Cells.Count < 5)
                {
                    continue;
                }
                var id = Convert.ToString(row.Cells[0].Value) ?? string.Empty;
                var title = Convert.ToString(row.Cells[2].Value) ?? string.Empty;
                var message = Convert.ToString(row.Cells[3].Value) ?? string.Empty;
                lines.Add("#" + id + " " + title + ": " + message);
            }

            if (lines.Count == 0 && _grid.CurrentRow != null && _grid.CurrentRow.Cells.Count >= 5)
            {
                var id = Convert.ToString(_grid.CurrentRow.Cells[0].Value) ?? string.Empty;
                var title = Convert.ToString(_grid.CurrentRow.Cells[2].Value) ?? string.Empty;
                var message = Convert.ToString(_grid.CurrentRow.Cells[3].Value) ?? string.Empty;
                lines.Add("#" + id + " " + title + ": " + message);
            }

            if (lines.Count == 0)
            {
                _statusLabel.Text = "Выберите уведомления для копирования.";
                return;
            }

            var payload = string.Join(Environment.NewLine, lines);
            if (ClipboardService.TrySetText(payload, out var error))
            {
                _statusLabel.Text = "Скопировано уведомлений: " + lines.Count;
                return;
            }

            _statusLabel.Text = error;
        }
    }
}
