using System;
using System.Drawing;
using System.Threading.Tasks;
using System.Windows.Forms;
using ItMaster.Desktop.Infrastructure.Api;
using ItMaster.Desktop.Models;

namespace ItMaster.Desktop.Forms
{
    public class ScheduleControl : UserControl
    {
        private readonly ApiClient _apiClient;
        private readonly string _token;
        private readonly Label _statusLabel;
        private readonly DataGridView _grid;
        private readonly DateTimePicker _weekPicker;

        public ScheduleControl(ApiClient apiClient, string token)
        {
            _apiClient = apiClient;
            _token = token;

            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var topPanel = new Panel { Dock = DockStyle.Top, Height = 56 };
            var title = new Label
            {
                Text = "Расписание",
                Left = 12,
                Top = 8,
                Width = 220,
                Font = new Font("Segoe UI", 13, FontStyle.Bold),
            };

            _weekPicker = new DateTimePicker
            {
                Left = 240,
                Top = 14,
                Width = 140,
                Format = DateTimePickerFormat.Short,
                Value = DateTime.Today,
            };

            var loadButton = new Button
            {
                Left = 390,
                Top = 13,
                Width = 120,
                Height = 28,
                Text = "Обновить",
            };
            loadButton.Click += async delegate { await LoadScheduleAsync(); };
            var prevWeekButton = new Button
            {
                Left = 520,
                Top = 13,
                Width = 34,
                Height = 28,
                Text = "<",
            };
            prevWeekButton.Click += async delegate
            {
                _weekPicker.Value = _weekPicker.Value.AddDays(-7);
                await LoadScheduleAsync();
            };
            var nextWeekButton = new Button
            {
                Left = 558,
                Top = 13,
                Width = 34,
                Height = 28,
                Text = ">",
            };
            nextWeekButton.Click += async delegate
            {
                _weekPicker.Value = _weekPicker.Value.AddDays(7);
                await LoadScheduleAsync();
            };

            _statusLabel = new Label
            {
                Left = 602,
                Top = 18,
                Width = 560,
                Height = 24,
            };

            topPanel.Controls.Add(title);
            topPanel.Controls.Add(_weekPicker);
            topPanel.Controls.Add(loadButton);
            topPanel.Controls.Add(prevWeekButton);
            topPanel.Controls.Add(nextWeekButton);
            topPanel.Controls.Add(_statusLabel);

            _grid = new DataGridView
            {
                Dock = DockStyle.Fill,
                ReadOnly = true,
                AutoGenerateColumns = false,
                AllowUserToAddRows = false,
                AllowUserToDeleteRows = false,
                SelectionMode = DataGridViewSelectionMode.FullRowSelect,
            };

            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "День", DataPropertyName = "WeekdayLabel", Width = 130 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Время", DataPropertyName = "TimeRange", Width = 120 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Предмет", DataPropertyName = "Subject", Width = 260 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Преподаватель", DataPropertyName = "Teacher", Width = 220 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Кабинет", DataPropertyName = "Room", Width = 90 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Корпус", DataPropertyName = "BuildingLabel", Width = 120 });

            Controls.Add(_grid);
            Controls.Add(topPanel);

            Load += async delegate { await LoadScheduleAsync(); };
        }

        private async Task LoadScheduleAsync()
        {
            if (string.IsNullOrWhiteSpace(_token))
            {
                _statusLabel.Text = "Сессия не найдена. Выполните вход заново.";
                return;
            }

            try
            {
                _statusLabel.Text = "Загрузка...";
                var monday = GetMonday(_weekPicker.Value);
                var data = await _apiClient.ScheduleAsync(_token, monday.ToString("yyyy-MM-dd"));
                Bind(data);
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private void Bind(ScheduleResponse response)
        {
            var list = new BindingSource();
            var rows = new System.Collections.Generic.List<object>();

            if (response != null && response.Entries != null)
            {
                foreach (var entry in response.Entries)
                {
                    rows.Add(new
                    {
                        entry.WeekdayLabel,
                        TimeRange = (entry.StartTime ?? "") + " - " + (entry.EndTime ?? ""),
                        entry.Subject,
                        entry.Teacher,
                        entry.Room,
                        entry.BuildingLabel,
                    });
                }
            }

            list.DataSource = rows;
            _grid.DataSource = list;
            _statusLabel.Text = "Неделя: " + (response?.WeekStart ?? "-") + ", группа: " + (response?.GroupName ?? "-") + ", занятий: " + rows.Count;
        }

        private static DateTime GetMonday(DateTime date)
        {
            var diff = (7 + (date.DayOfWeek - DayOfWeek.Monday)) % 7;
            return date.AddDays(-1 * diff).Date;
        }
    }
}
