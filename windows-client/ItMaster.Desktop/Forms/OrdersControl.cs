using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;
using ItMaster.Desktop.Infrastructure.Api;
using ItMaster.Desktop.Models;

namespace ItMaster.Desktop.Forms
{
    public class OrdersControl : UserControl
    {
        private readonly ApiClient _apiClient;
        private readonly string _token;
        private readonly bool _canAdmin;
        private readonly ComboBox _categoryCombo;
        private readonly TextBox _descriptionBox;
        private readonly TextBox _roomBox;
        private readonly TextBox _searchBox;
        private readonly ComboBox _statusFilter;
        private readonly Label _statusLabel;
        private readonly Label _detailsLabel;
        private readonly DataGridView _grid;
        private List<OrderCategoryDto> _categories = new List<OrderCategoryDto>();
        private List<OrderItemDto> _orders = new List<OrderItemDto>();

        public OrdersControl(ApiClient apiClient, string token, bool canAdmin)
        {
            _apiClient = apiClient;
            _token = token;
            _canAdmin = canAdmin;
            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var top = new Panel { Dock = DockStyle.Top, Height = 154 };
            var title = new Label
            {
                Text = "Заявки",
                Left = 12,
                Top = 8,
                Width = 180,
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
            };

            _categoryCombo = new ComboBox { Left = 12, Top = 36, Width = 220, DropDownStyle = ComboBoxStyle.DropDownList };
            _roomBox = new TextBox { Left = 244, Top = 36, Width = 120 };
            _descriptionBox = new TextBox { Left = 376, Top = 36, Width = 420 };
            var createBtn = new Button { Left = 808, Top = 35, Width = 110, Height = 28, Text = "Создать" };
            createBtn.Click += async delegate { await CreateOrderAsync(); };
            var refreshBtn = new Button { Left = 928, Top = 35, Width = 110, Height = 28, Text = "Обновить" };
            refreshBtn.Click += async delegate { await LoadOrdersAsync(); };
            _descriptionBox.KeyDown += async delegate (object sender, KeyEventArgs e)
            {
                if (e.KeyCode == Keys.Enter && !e.Shift)
                {
                    e.SuppressKeyPress = true;
                    await CreateOrderAsync();
                }
            };
            _grid.KeyDown += async delegate (object sender, KeyEventArgs e)
            {
                if (e.Control && e.KeyCode == Keys.R)
                {
                    e.SuppressKeyPress = true;
                    await LoadOrdersAsync();
                }
            };
            _searchBox = new TextBox { Left = 12, Top = 108, Width = 340 };
            _searchBox.TextChanged += delegate { ApplyFilter(); };
            _statusFilter = new ComboBox { Left = 360, Top = 108, Width = 180, DropDownStyle = ComboBoxStyle.DropDownList };
            _statusFilter.Items.AddRange(new object[] { "Все статусы", "Новая", "В работе", "Обработана", "Закрыта" });
            _statusFilter.SelectedIndex = 0;
            _statusFilter.SelectedIndexChanged += delegate { ApplyFilter(); };

            var setNewBtn = new Button { Left = 12, Top = 72, Width = 100, Height = 26, Text = "Статус: Новая", Enabled = _canAdmin };
            var setWorkBtn = new Button { Left = 118, Top = 72, Width = 100, Height = 26, Text = "В работе", Enabled = _canAdmin };
            var setDoneBtn = new Button { Left = 224, Top = 72, Width = 100, Height = 26, Text = "Обработана", Enabled = _canAdmin };
            var setClosedBtn = new Button { Left = 330, Top = 72, Width = 100, Height = 26, Text = "Закрыта", Enabled = _canAdmin };
            setNewBtn.Click += async delegate { await SetStatusAsync(0); };
            setWorkBtn.Click += async delegate { await SetStatusAsync(1); };
            setDoneBtn.Click += async delegate { await SetStatusAsync(2); };
            setClosedBtn.Click += async delegate { await SetStatusAsync(3); };

            _statusLabel = new Label { Left = 450, Top = 76, Width = 680, Height = 24 };
            _detailsLabel = new Label { Left = 12, Top = 132, Width = 1120, Height = 18, ForeColor = Color.DimGray };

            top.Controls.Add(title);
            top.Controls.Add(_categoryCombo);
            top.Controls.Add(_roomBox);
            top.Controls.Add(_descriptionBox);
            top.Controls.Add(createBtn);
            top.Controls.Add(refreshBtn);
            top.Controls.Add(setNewBtn);
            top.Controls.Add(setWorkBtn);
            top.Controls.Add(setDoneBtn);
            top.Controls.Add(setClosedBtn);
            top.Controls.Add(_searchBox);
            top.Controls.Add(_statusFilter);
            top.Controls.Add(_statusLabel);
            top.Controls.Add(_detailsLabel);

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
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Статус", DataPropertyName = "StatusText", Width = 120 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Категория", DataPropertyName = "CategoryName", Width = 160 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Описание", DataPropertyName = "Description", Width = 320 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Каб.", DataPropertyName = "Room", Width = 70 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Сотрудник", DataPropertyName = "EmployeeFullName", Width = 180 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Создано", DataPropertyName = "CreatedAt", Width = 180 });
            _grid.SelectionChanged += Grid_SelectionChanged;

            Controls.Add(_grid);
            Controls.Add(top);

            Load += async delegate
            {
                await LoadCategoriesAsync();
                await LoadOrdersAsync();
            };
        }

        private async Task LoadCategoriesAsync()
        {
            try
            {
                var response = await _apiClient.OrdersCategoriesAsync(_token);
                _categories = response?.Items ?? new List<OrderCategoryDto>();
                _categoryCombo.Items.Clear();
                foreach (var c in _categories)
                {
                    _categoryCombo.Items.Add(new CategoryItem(c.Id, c.Name));
                }
                if (_categoryCombo.Items.Count > 0)
                {
                    _categoryCombo.SelectedIndex = 0;
                }
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task LoadOrdersAsync()
        {
            try
            {
                var response = await _apiClient.OrdersMyAsync(_token);
                _orders = response?.Items ?? new List<OrderItemDto>();
                ApplyFilter();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task CreateOrderAsync()
        {
            if (!(_categoryCombo.SelectedItem is CategoryItem selected))
            {
                _statusLabel.Text = "Выберите категорию.";
                return;
            }
            if (string.IsNullOrWhiteSpace(_descriptionBox.Text))
            {
                _statusLabel.Text = "Введите описание.";
                return;
            }

            try
            {
                var result = await _apiClient.OrdersCreateAsync(_token, _descriptionBox.Text.Trim(), selected.Id, _roomBox.Text.Trim());
                _descriptionBox.Text = string.Empty;
                _roomBox.Text = string.Empty;
                _statusLabel.Text = result?.Message ?? "Заявка создана.";
                await LoadOrdersAsync();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task SetStatusAsync(int code)
        {
            if (!_canAdmin)
            {
                _statusLabel.Text = "Недостаточно прав.";
                return;
            }
            var selectedOrderIds = GetSelectedOrderIds();
            if (selectedOrderIds.Count == 0)
            {
                _statusLabel.Text = "Выберите заявку.";
                return;
            }

            try
            {
                var success = 0;
                var firstMessage = string.Empty;
                foreach (var orderId in selectedOrderIds)
                {
                    var result = await _apiClient.OrdersSetStatusAsync(_token, orderId, code);
                    if (string.IsNullOrWhiteSpace(firstMessage))
                    {
                        firstMessage = result?.Message ?? string.Empty;
                    }
                    success++;
                }

                _statusLabel.Text = (string.IsNullOrWhiteSpace(firstMessage) ? "Статус обновлен." : firstMessage) + " Изменено: " + success;
                await LoadOrdersAsync();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private static string StatusText(int status)
        {
            switch (status)
            {
                case 0: return "Новая";
                case 1: return "В работе";
                case 2: return "Обработана";
                case 3: return "Закрыта";
                default: return status.ToString();
            }
        }

        private void ApplyFilter()
        {
            var term = (_searchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
            var filterStatus = _statusFilter.SelectedIndex - 1;
            var filtered = _orders.Where(o =>
                (filterStatus < 0 || o.Status == filterStatus)
                && (term == string.Empty
                    || (o.Description ?? string.Empty).ToLowerInvariant().Contains(term)
                    || (o.CategoryName ?? string.Empty).ToLowerInvariant().Contains(term)
                    || (o.EmployeeFullName ?? string.Empty).ToLowerInvariant().Contains(term)
                    || (o.Room ?? string.Empty).ToLowerInvariant().Contains(term)
                    || o.Id.ToString().Contains(term))
            ).ToList();

            _grid.DataSource = new BindingSource
            {
                DataSource = filtered.Select(o => new
                {
                    o.Id,
                    StatusText = StatusText(o.Status),
                    o.CategoryName,
                    o.Description,
                    o.Room,
                    o.EmployeeFullName,
                    o.CreatedAt,
                }).ToList()
            };
            _statusLabel.Text = "Заявок: " + filtered.Count + " из " + _orders.Count;
            Grid_SelectionChanged(this, EventArgs.Empty);
        }

        private int? GetSelectedOrderId()
        {
            if (_grid.CurrentRow == null || _grid.CurrentRow.Cells.Count == 0)
            {
                return null;
            }
            var idCell = _grid.CurrentRow.Cells[0].Value;
            if (idCell == null)
            {
                return null;
            }
            if (!int.TryParse(idCell.ToString(), out var id))
            {
                return null;
            }
            return id;
        }

        private List<int> GetSelectedOrderIds()
        {
            var ids = new List<int>();
            foreach (DataGridViewRow row in _grid.SelectedRows)
            {
                if (row == null || row.Cells.Count == 0)
                {
                    continue;
                }
                var cell = row.Cells[0].Value;
                if (cell == null)
                {
                    continue;
                }
                if (int.TryParse(cell.ToString(), out var id))
                {
                    ids.Add(id);
                }
            }

            if (ids.Count == 0)
            {
                var single = GetSelectedOrderId();
                if (single != null)
                {
                    ids.Add(single.Value);
                }
            }

            return ids.Distinct().ToList();
        }

        private void Grid_SelectionChanged(object sender, EventArgs e)
        {
            var orderId = GetSelectedOrderId();
            if (orderId == null)
            {
                _detailsLabel.Text = string.Empty;
                return;
            }

            var order = _orders.FirstOrDefault(o => o.Id == orderId.Value);
            if (order == null)
            {
                _detailsLabel.Text = string.Empty;
                return;
            }

            _detailsLabel.Text = "ID " + order.Id + " | " + StatusText(order.Status) + " | " + (order.CategoryName ?? "-") + " | " + (order.EmployeeFullName ?? "-");
        }

        private sealed class CategoryItem
        {
            public CategoryItem(int id, string name)
            {
                Id = id;
                Name = name;
            }

            public int Id { get; }
            public string Name { get; }

            public override string ToString() => Name;
        }
    }
}
