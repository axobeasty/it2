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
    public class WikiControl : UserControl
    {
        private readonly ApiClient _apiClient;
        private readonly string _token;
        private readonly DataGridView _listGrid;
        private readonly TextBox _bodyBox;
        private readonly TextBox _searchBox;
        private readonly Label _titleLabel;
        private readonly Label _statusLabel;
        private System.Collections.Generic.List<WikiPageListItemDto> _items = new System.Collections.Generic.List<WikiPageListItemDto>();

        public WikiControl(ApiClient apiClient, string token)
        {
            _apiClient = apiClient;
            _token = token;
            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var split = new SplitContainer
            {
                Dock = DockStyle.Fill,
                SplitterDistance = 420,
            };

            var leftTop = new Panel { Dock = DockStyle.Top, Height = 48 };
            var title = new Label
            {
                Text = "Wiki",
                Left = 10,
                Top = 10,
                Width = 100,
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
            };
            var refreshButton = new Button
            {
                Left = 120,
                Top = 9,
                Width = 100,
                Height = 28,
                Text = "Обновить",
            };
            refreshButton.Click += async delegate { await LoadListAsync(); };
            var copySlugButton = new Button
            {
                Left = 410,
                Top = 9,
                Width = 120,
                Height = 28,
                Text = "Копировать slug",
            };
            copySlugButton.Click += delegate { CopySelectedSlug(); };
            _searchBox = new TextBox { Left = 230, Top = 9, Width = 170 };
            _searchBox.TextChanged += delegate { ApplyFilter(); };
            _searchBox.KeyDown += async delegate (object sender, KeyEventArgs e)
            {
                if (e.KeyCode == Keys.Enter)
                {
                    e.SuppressKeyPress = true;
                    if (_listGrid.Rows.Count > 0)
                    {
                        _listGrid.ClearSelection();
                        _listGrid.Rows[0].Selected = true;
                        _listGrid.CurrentCell = _listGrid.Rows[0].Cells[0];
                        await LoadSelectedArticleAsync();
                    }
                }
            };
            leftTop.Controls.Add(title);
            leftTop.Controls.Add(refreshButton);
            leftTop.Controls.Add(_searchBox);
            leftTop.Controls.Add(copySlugButton);

            _listGrid = new DataGridView
            {
                Dock = DockStyle.Fill,
                ReadOnly = true,
                AutoGenerateColumns = false,
                AllowUserToAddRows = false,
                AllowUserToDeleteRows = false,
                SelectionMode = DataGridViewSelectionMode.FullRowSelect,
            };
            _listGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Заголовок", DataPropertyName = "Title", Width = 250 });
            _listGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Slug", DataPropertyName = "Slug", Width = 140 });
            _listGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Обновлено", DataPropertyName = "UpdatedAt", Width = 170 });
            _listGrid.SelectionChanged += async delegate { await LoadSelectedArticleAsync(); };

            split.Panel1.Controls.Add(_listGrid);
            split.Panel1.Controls.Add(leftTop);

            var rightTop = new Panel { Dock = DockStyle.Top, Height = 70 };
            _titleLabel = new Label
            {
                Left = 10,
                Top = 8,
                Width = 700,
                Height = 24,
                Font = new Font("Segoe UI", 11, FontStyle.Bold),
            };
            _statusLabel = new Label
            {
                Left = 10,
                Top = 36,
                Width = 700,
                Height = 24,
                ForeColor = Color.DimGray,
            };
            rightTop.Controls.Add(_titleLabel);
            rightTop.Controls.Add(_statusLabel);

            _bodyBox = new TextBox
            {
                Dock = DockStyle.Fill,
                Multiline = true,
                ReadOnly = true,
                ScrollBars = ScrollBars.Both,
                Font = new Font("Consolas", 10),
            };

            split.Panel2.Controls.Add(_bodyBox);
            split.Panel2.Controls.Add(rightTop);

            Controls.Add(split);

            Load += async delegate { await LoadListAsync(); };
        }

        private async Task LoadListAsync()
        {
            if (string.IsNullOrWhiteSpace(_token))
            {
                _statusLabel.Text = "Нет токена сессии.";
                return;
            }

            try
            {
                _statusLabel.Text = "Загрузка статей...";
                var response = await _apiClient.WikiListAsync(_token);
                _items = response?.Items ?? new System.Collections.Generic.List<WikiPageListItemDto>();
                ApplyFilter();
                if (_listGrid.Rows.Count > 0)
                {
                    _listGrid.ClearSelection();
                    _listGrid.Rows[0].Selected = true;
                    _listGrid.CurrentCell = _listGrid.Rows[0].Cells[0];
                    await LoadSelectedArticleAsync();
                }
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task LoadSelectedArticleAsync()
        {
            if (_listGrid.CurrentRow == null || _listGrid.CurrentRow.Cells.Count < 2)
            {
                return;
            }

            var slug = Convert.ToString(_listGrid.CurrentRow.Cells[1].Value);
            if (string.IsNullOrWhiteSpace(slug))
            {
                return;
            }

            try
            {
                var response = await _apiClient.WikiShowAsync(_token, slug);
                var page = response?.Page;
                _titleLabel.Text = page?.Title ?? slug;
                _bodyBox.Text = page?.Body ?? string.Empty;
                _statusLabel.Text = "Slug: " + (page?.Slug ?? slug) + " | Обновлено: " + (page?.UpdatedAt ?? "-") + " | Автор правки: " + (page?.UpdatedBy ?? "-");
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private void ApplyFilter()
        {
            var term = (_searchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
            var filtered = _items
                .Where(i =>
                    term == string.Empty
                    || (i.Title ?? string.Empty).ToLowerInvariant().Contains(term)
                    || (i.Slug ?? string.Empty).ToLowerInvariant().Contains(term))
                .ToList();

            _listGrid.DataSource = new BindingSource
            {
                DataSource = filtered.Select(i => new { i.Title, i.Slug, i.UpdatedAt }).ToList()
            };
            _statusLabel.Text = "Статей: " + filtered.Count + " из " + _items.Count;
        }

        private void CopySelectedSlug()
        {
            if (_listGrid.CurrentRow == null || _listGrid.CurrentRow.Cells.Count < 2)
            {
                _statusLabel.Text = "Выберите статью.";
                return;
            }

            var slug = Convert.ToString(_listGrid.CurrentRow.Cells[1].Value) ?? string.Empty;
            if (string.IsNullOrWhiteSpace(slug))
            {
                _statusLabel.Text = "Slug не найден.";
                return;
            }

            if (ClipboardService.TrySetText(slug, out var error))
            {
                _statusLabel.Text = "Slug скопирован: " + slug;
                return;
            }

            _statusLabel.Text = error;
        }
    }
}
