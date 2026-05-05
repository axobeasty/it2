using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using ItMaster.Desktop.Infrastructure.Api;
using ItMaster.Desktop.Infrastructure.Ui;
using ItMaster.Desktop.Models;

namespace ItMaster.Desktop.Forms
{
    public class TestsControl : UserControl
    {
        private readonly ApiClient _apiClient;
        private readonly string _token;
        private readonly Label _statusLabel;
        private readonly DataGridView _testsGrid;
        private readonly DataGridView _questionsGrid;
        private readonly Panel _answerPanel;
        private readonly Button _startButton;
        private readonly Button _submitButton;
        private readonly TextBox _searchBox;

        private List<TestSummaryDto> _tests = new List<TestSummaryDto>();
        private TestDetailsDto _activeTest;
        private readonly Dictionary<int, object> _answers = new Dictionary<int, object>();

        public TestsControl(ApiClient apiClient, string token)
        {
            _apiClient = apiClient;
            _token = token;
            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var split = new SplitContainer
            {
                Dock = DockStyle.Fill,
                Orientation = Orientation.Horizontal,
                SplitterDistance = 290,
            };

            var topPanel = new Panel { Dock = DockStyle.Top, Height = 48 };
            var title = new Label
            {
                Text = "Тесты",
                Left = 12,
                Top = 10,
                Width = 180,
                Font = new Font("Segoe UI", 13, FontStyle.Bold),
            };

            _startButton = new Button { Left = 220, Top = 9, Width = 170, Height = 28, Text = "Начать попытку" };
            _startButton.Click += async delegate { await StartSelectedTestAsync(); };

            _submitButton = new Button { Left = 400, Top = 9, Width = 210, Height = 28, Text = "Отправить пустые ответы" };
            _submitButton.Click += async delegate { await SubmitCurrentTestAsync(); };
            var copyQuestionButton = new Button { Left = 810, Top = 9, Width = 150, Height = 28, Text = "Копировать вопрос" };
            copyQuestionButton.Click += delegate { CopySelectedQuestion(); };

            _searchBox = new TextBox { Left = 970, Top = 9, Width = 170 };
            _searchBox.TextChanged += delegate { BindTestsGrid(); };
            _searchBox.KeyDown += async delegate (object sender, KeyEventArgs e)
            {
                if (e.KeyCode == Keys.Enter)
                {
                    e.SuppressKeyPress = true;
                    await StartSelectedTestAsync();
                }
            };
            _statusLabel = new Label { Left = 12, Top = 42, Width = 1128, Height = 24 };

            topPanel.Controls.Add(title);
            topPanel.Controls.Add(_startButton);
            topPanel.Controls.Add(_submitButton);
            topPanel.Controls.Add(copyQuestionButton);
            topPanel.Controls.Add(_searchBox);
            topPanel.Controls.Add(_statusLabel);
            topPanel.Height = 70;

            _testsGrid = CreateTestsGrid();
            _questionsGrid = CreateQuestionsGrid();
            _answerPanel = new Panel
            {
                Dock = DockStyle.Bottom,
                Height = 190,
                BorderStyle = BorderStyle.FixedSingle,
                Padding = new Padding(8),
            };
            _questionsGrid.SelectionChanged += QuestionsGrid_SelectionChanged;
            _questionsGrid.KeyDown += delegate (object sender, KeyEventArgs e)
            {
                if (e.Control && e.KeyCode == Keys.C)
                {
                    e.SuppressKeyPress = true;
                    CopySelectedQuestion();
                }
            };
            _testsGrid.CellDoubleClick += async delegate { await StartSelectedTestAsync(); };

            split.Panel1.Controls.Add(_testsGrid);
            split.Panel2.Controls.Add(_questionsGrid);
            split.Panel2.Controls.Add(_answerPanel);

            Controls.Add(split);
            Controls.Add(topPanel);

            Load += async delegate { await LoadTestsAsync(); };
        }

        private DataGridView CreateTestsGrid()
        {
            var grid = new DataGridView
            {
                Dock = DockStyle.Fill,
                ReadOnly = true,
                AutoGenerateColumns = false,
                AllowUserToAddRows = false,
                AllowUserToDeleteRows = false,
                SelectionMode = DataGridViewSelectionMode.FullRowSelect,
            };

            grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "ID", DataPropertyName = "Id", Width = 60 });
            grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Название", DataPropertyName = "Title", Width = 340 });
            grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Лимит", DataPropertyName = "Attempts", Width = 120 });
            grid.Columns.Add(new DataGridViewCheckBoxColumn { HeaderText = "Можно начать", DataPropertyName = "CanStart", Width = 120 });
            return grid;
        }

        private DataGridView CreateQuestionsGrid()
        {
            var grid = new DataGridView
            {
                Dock = DockStyle.Fill,
                ReadOnly = true,
                AutoGenerateColumns = false,
                AllowUserToAddRows = false,
                AllowUserToDeleteRows = false,
                SelectionMode = DataGridViewSelectionMode.FullRowSelect,
            };
            grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "ID", DataPropertyName = "Id", Width = 60 });
            grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Тип", DataPropertyName = "Type", Width = 120 });
            grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Вопрос", DataPropertyName = "QuestionText", Width = 780 });
            return grid;
        }

        private async Task LoadTestsAsync()
        {
            if (string.IsNullOrWhiteSpace(_token))
            {
                _statusLabel.Text = "Сессия не найдена. Выполните вход заново.";
                return;
            }

            try
            {
                _statusLabel.Text = "Загрузка тестов...";
                var response = await _apiClient.TestsListAsync(_token);
                _tests = response?.Tests ?? new List<TestSummaryDto>();
                BindTestsGrid();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task StartSelectedTestAsync()
        {
            var selected = GetSelectedTestId();
            if (selected == null)
            {
                _statusLabel.Text = "Выберите тест в таблице.";
                return;
            }

            try
            {
                _statusLabel.Text = "Запуск попытки...";
                var session = await _apiClient.TestBeginAsync(_token, selected.Value);
                _activeTest = session?.Test;
                _answers.Clear();
                var questions = _activeTest?.Questions ?? new List<TestQuestionDto>();
                _questionsGrid.DataSource = new BindingSource
                {
                    DataSource = questions.Select(q => new { q.Id, q.Type, q.QuestionText }).ToList()
                };
                _statusLabel.Text = "Тест \"" + (_activeTest?.Title ?? selected.Value.ToString()) + "\": вопросов " + questions.Count;
                _answerPanel.Controls.Clear();
                _submitButton.Text = "Отправить ответы";
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task SubmitCurrentTestAsync()
        {
            if (_activeTest == null)
            {
                _statusLabel.Text = "Сначала начните попытку теста.";
                return;
            }

            try
            {
                _statusLabel.Text = "Отправка...";
                var answersJson = BuildAnswersJson();
                var result = await _apiClient.TestSubmitAsync(_token, _activeTest.Id, answersJson);
                _statusLabel.Text = (result?.Message ?? "Отправлено.") + " Оценка: " + (result?.Attempt?.GradeLabel ?? "-");
                _submitButton.Text = "Отправить пустые ответы";
                await LoadTestsAsync();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private void QuestionsGrid_SelectionChanged(object sender, EventArgs e)
        {
            if (_activeTest == null || _questionsGrid.CurrentRow == null)
            {
                return;
            }

            var idx = _questionsGrid.CurrentRow.Index;
            if (idx < 0 || idx >= _activeTest.Questions.Count)
            {
                return;
            }

            RenderAnswerEditor(_activeTest.Questions[idx]);
        }

        private void RenderAnswerEditor(TestQuestionDto question)
        {
            _answerPanel.Controls.Clear();

            var title = new Label
            {
                Text = "Ответ на вопрос #" + question.Id + " (" + question.Type + ")",
                Left = 6,
                Top = 6,
                Width = 900,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
            };
            _answerPanel.Controls.Add(title);

            var qLabel = new Label
            {
                Text = question.QuestionText ?? string.Empty,
                Left = 6,
                Top = 30,
                Width = 900,
                Height = 40,
            };
            _answerPanel.Controls.Add(qLabel);

            if (string.Equals(question.Type, "single", StringComparison.OrdinalIgnoreCase))
            {
                var combo = new ComboBox { Left = 6, Top = 80, Width = 600, DropDownStyle = ComboBoxStyle.DropDownList };
                var options = question.Options ?? new List<string>();
                for (var i = 0; i < options.Count; i++)
                {
                    combo.Items.Add((i + 1) + ". " + options[i]);
                }
                combo.SelectedIndexChanged += delegate { _answers[question.Id] = combo.SelectedIndex + 1; };
                _answerPanel.Controls.Add(combo);
                return;
            }

            if (string.Equals(question.Type, "multiple", StringComparison.OrdinalIgnoreCase))
            {
                var options = question.Options ?? new List<string>();
                var checkedList = new CheckedListBox { Left = 6, Top = 80, Width = 700, Height = 90 };
                for (var i = 0; i < options.Count; i++)
                {
                    checkedList.Items.Add((i + 1) + ". " + options[i], false);
                }
                checkedList.ItemCheck += delegate
                {
                    BeginInvoke((Action)(() =>
                    {
                        var selected = new List<int>();
                        for (var i = 0; i < checkedList.Items.Count; i++)
                        {
                            if (checkedList.GetItemChecked(i))
                            {
                                selected.Add(i + 1);
                            }
                        }
                        _answers[question.Id] = selected;
                    }));
                };
                _answerPanel.Controls.Add(checkedList);
                return;
            }

            if (string.Equals(question.Type, "match", StringComparison.OrdinalIgnoreCase))
            {
                var left = question.Left ?? new List<string>();
                var right = question.Right ?? new List<string>();
                var y = 80;
                var map = new Dictionary<string, string>();
                for (var i = 0; i < left.Count; i++)
                {
                    var leftValue = left[i];
                    var leftLabel = new Label { Left = 6, Top = y + 4, Width = 280, Text = leftValue };
                    var combo = new ComboBox { Left = 300, Top = y, Width = 260, DropDownStyle = ComboBoxStyle.DropDownList };
                    foreach (var item in right)
                    {
                        combo.Items.Add(item);
                    }
                    combo.SelectedIndexChanged += delegate
                    {
                        if (combo.SelectedItem != null)
                        {
                            map[leftValue] = combo.SelectedItem.ToString();
                            _answers[question.Id] = new Dictionary<string, string>(map);
                        }
                    };
                    _answerPanel.Controls.Add(leftLabel);
                    _answerPanel.Controls.Add(combo);
                    y += 30;
                }
                return;
            }

            var textBox = new TextBox { Left = 6, Top = 80, Width = 700 };
            textBox.TextChanged += delegate { _answers[question.Id] = textBox.Text ?? string.Empty; };
            _answerPanel.Controls.Add(textBox);
        }

        private string BuildAnswersJson()
        {
            var sb = new StringBuilder();
            sb.Append("{");
            var first = true;
            foreach (var item in _answers)
            {
                if (!first)
                {
                    sb.Append(",");
                }
                first = false;
                sb.Append("\"").Append(item.Key).Append("\":");
                sb.Append(SerializeValue(item.Value));
            }
            sb.Append("}");
            return sb.ToString();
        }

        private static string SerializeValue(object value)
        {
            if (value == null)
            {
                return "null";
            }

            if (value is int intValue)
            {
                return intValue.ToString();
            }

            if (value is string stringValue)
            {
                return "\"" + EscapeJson(stringValue) + "\"";
            }

            if (value is List<int> intList)
            {
                return "[" + string.Join(",", intList.Select(x => x.ToString())) + "]";
            }

            if (value is Dictionary<string, string> map)
            {
                var pairs = map.Select(kv => "\"" + EscapeJson(kv.Key) + "\":\"" + EscapeJson(kv.Value) + "\"");
                return "{" + string.Join(",", pairs) + "}";
            }

            return "\"\"";
        }

        private static string EscapeJson(string value)
        {
            return (value ?? string.Empty).Replace("\\", "\\\\").Replace("\"", "\\\"");
        }

        private int? GetSelectedTestId()
        {
            if (_testsGrid.CurrentRow == null || _testsGrid.CurrentRow.Cells.Count == 0)
            {
                return null;
            }
            var idValue = _testsGrid.CurrentRow.Cells[0].Value;
            if (idValue == null)
            {
                return null;
            }
            if (!int.TryParse(idValue.ToString(), out var id))
            {
                return null;
            }
            return id;
        }

        private void BindTestsGrid()
        {
            var term = (_searchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
            var filtered = _tests.Where(t =>
                term == string.Empty
                || (t.Title ?? string.Empty).ToLowerInvariant().Contains(term)
                || (t.Description ?? string.Empty).ToLowerInvariant().Contains(term)
                || t.Id.ToString().Contains(term)
            ).ToList();

            var rows = filtered.Select(t => new
            {
                t.Id,
                t.Title,
                Attempts = t.AttemptsUsed + "/" + t.AttemptsLimit,
                t.CanStart,
            }).ToList();
            _testsGrid.DataSource = new BindingSource { DataSource = rows };
            _statusLabel.Text = "Тестов: " + rows.Count + " из " + _tests.Count;
        }

        private void CopySelectedQuestion()
        {
            if (_questionsGrid.CurrentRow == null || _questionsGrid.CurrentRow.Cells.Count < 3)
            {
                _statusLabel.Text = "Выберите вопрос для копирования.";
                return;
            }

            var id = Convert.ToString(_questionsGrid.CurrentRow.Cells[0].Value) ?? string.Empty;
            var type = Convert.ToString(_questionsGrid.CurrentRow.Cells[1].Value) ?? string.Empty;
            var text = Convert.ToString(_questionsGrid.CurrentRow.Cells[2].Value) ?? string.Empty;
            var payload = "Q#" + id + " [" + type + "] " + text;
            if (string.IsNullOrWhiteSpace(payload))
            {
                _statusLabel.Text = "Нет данных для копирования.";
                return;
            }

            if (ClipboardService.TrySetText(payload, out var error))
            {
                _statusLabel.Text = "Вопрос скопирован в буфер обмена.";
                return;
            }

            _statusLabel.Text = error;
        }
    }
}
