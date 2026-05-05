using System;
using System.Drawing;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;
using ItMaster.Desktop.Infrastructure.Api;
using ItMaster.Desktop.Infrastructure.Ui;
using ItMaster.Desktop.Models;

namespace ItMaster.Desktop.Forms
{
    public class TestStatsControl : UserControl
    {
        private readonly ApiClient _apiClient;
        private readonly string _token;
        private readonly ComboBox _groupCombo;
        private readonly NumericUpDown _pageInput;
        private readonly Label _statusLabel;
        private readonly DataGridView _grid;

        public TestStatsControl(ApiClient apiClient, string token)
        {
            _apiClient = apiClient;
            _token = token;
            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var top = new Panel { Dock = DockStyle.Top, Height = 54 };
            var title = new Label
            {
                Text = "Статистика тестов",
                Left = 12,
                Top = 12,
                Width = 200,
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
            };
            _groupCombo = new ComboBox { Left = 220, Top = 12, Width = 220, DropDownStyle = ComboBoxStyle.DropDownList };
            _pageInput = new NumericUpDown { Left = 450, Top = 12, Width = 70, Minimum = 1, Maximum = 999, Value = 1 };
            var loadBtn = new Button { Left = 530, Top = 11, Width = 110, Height = 28, Text = "Загрузить" };
            loadBtn.Click += async delegate { await LoadStatsAsync(); };
            var prevBtn = new Button { Left = 650, Top = 11, Width = 34, Height = 28, Text = "<" };
            var nextBtn = new Button { Left = 688, Top = 11, Width = 34, Height = 28, Text = ">" };
            prevBtn.Click += async delegate
            {
                if (_pageInput.Value > _pageInput.Minimum)
                {
                    _pageInput.Value -= 1;
                    await LoadStatsAsync();
                }
            };
            nextBtn.Click += async delegate
            {
                _pageInput.Value += 1;
                await LoadStatsAsync();
            };
            _statusLabel = new Label { Left = 730, Top = 15, Width = 420 };
            var copyBtn = new Button { Left = 930, Top = 11, Width = 210, Height = 28, Text = "Копировать выделенные" };
            copyBtn.Click += delegate { CopySelectedRows(); };

            top.Controls.Add(title);
            top.Controls.Add(_groupCombo);
            top.Controls.Add(_pageInput);
            top.Controls.Add(loadBtn);
            top.Controls.Add(prevBtn);
            top.Controls.Add(nextBtn);
            top.Controls.Add(_statusLabel);
            top.Controls.Add(copyBtn);

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
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Студент", DataPropertyName = "StudentFullName", Width = 180 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Группа", DataPropertyName = "GroupName", Width = 120 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Тест", DataPropertyName = "TestTitle", Width = 240 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Баллы", DataPropertyName = "ScoreText", Width = 90 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "%", DataPropertyName = "Percentage", Width = 70 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Оценка", DataPropertyName = "GradeLabel", Width = 110 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Дата", DataPropertyName = "SubmittedAt", Width = 180 });

            Controls.Add(_grid);
            Controls.Add(top);

            Load += async delegate { await InitAsync(); };
        }

        private async Task InitAsync()
        {
            if (string.IsNullOrWhiteSpace(_token))
            {
                _statusLabel.Text = "Нет токена сессии.";
                return;
            }

            try
            {
                var data = await _apiClient.TestStatsAsync(_token, 0, 1);
                _groupCombo.Items.Clear();
                _groupCombo.Items.Add(new GroupItem(0, "Все группы"));
                if (data.Groups != null)
                {
                    foreach (var g in data.Groups)
                    {
                        _groupCombo.Items.Add(new GroupItem(g.Id, g.Name));
                    }
                }
                _groupCombo.SelectedIndex = 0;
                BindAttempts(data);
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task LoadStatsAsync()
        {
            try
            {
                var groupId = 0;
                if (_groupCombo.SelectedItem is GroupItem selected)
                {
                    groupId = selected.Id;
                }

                var page = (int)_pageInput.Value;
                var data = await _apiClient.TestStatsAsync(_token, groupId, page);
                BindAttempts(data);
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private void BindAttempts(TestStatsResponse data)
        {
            var rows = (data.Attempts?.Data ?? new System.Collections.Generic.List<TestStatsAttemptRowDto>())
                .Select(a => new
                {
                    a.StudentFullName,
                    a.GroupName,
                    a.TestTitle,
                    ScoreText = a.Score + "/" + a.MaxScore,
                    Percentage = a.Percentage.ToString("0.##"),
                    a.GradeLabel,
                    a.SubmittedAt,
                }).ToList();
            _grid.DataSource = new BindingSource { DataSource = rows };
            _statusLabel.Text = "Страница " + (data.Attempts?.CurrentPage ?? 1) + "/" + (data.Attempts?.LastPage ?? 1) + ", всего: " + (data.Attempts?.Total ?? rows.Count);
        }

        private void CopySelectedRows()
        {
            var lines = new System.Collections.Generic.List<string>();
            foreach (DataGridViewRow row in _grid.SelectedRows)
            {
                if (row == null || row.Cells.Count < 7)
                {
                    continue;
                }
                var student = Convert.ToString(row.Cells[0].Value) ?? string.Empty;
                var group = Convert.ToString(row.Cells[1].Value) ?? string.Empty;
                var test = Convert.ToString(row.Cells[2].Value) ?? string.Empty;
                var score = Convert.ToString(row.Cells[3].Value) ?? string.Empty;
                var grade = Convert.ToString(row.Cells[5].Value) ?? string.Empty;
                lines.Add(student + " | " + group + " | " + test + " | " + score + " | " + grade);
            }

            if (lines.Count == 0)
            {
                _statusLabel.Text = "Выберите строки для копирования.";
                return;
            }

            var payload = string.Join(Environment.NewLine, lines);
            if (ClipboardService.TrySetText(payload, out var error))
            {
                _statusLabel.Text = "Скопировано строк: " + lines.Count;
                return;
            }

            _statusLabel.Text = error;
        }

        private sealed class GroupItem
        {
            public GroupItem(int id, string name)
            {
                Id = id;
                Name = name;
            }

            public int Id { get; }
            public string Name { get; }

            public override string ToString()
            {
                return Name;
            }
        }
    }
}
