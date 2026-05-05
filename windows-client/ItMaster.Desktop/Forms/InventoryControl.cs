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
    public class InventoryControl : UserControl
    {
        private readonly ApiClient _apiClient;
        private readonly string _token;
        private readonly bool _canAdmin;
        private readonly Label _statusLabel;
        private readonly TextBox _searchBox;
        private readonly DataGridView _grid;
        private List<InventoryItemDto> _items = new List<InventoryItemDto>();

        public InventoryControl(ApiClient apiClient, string token, bool canAdmin)
        {
            _apiClient = apiClient;
            _token = token;
            _canAdmin = canAdmin;
            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var top = new Panel { Dock = DockStyle.Top, Height = 80 };
            var title = new Label
            {
                Text = _canAdmin ? "Инвентарь (управление)" : "Мой инвентарь",
                Left = 12,
                Top = 10,
                Width = 260,
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
            };
            var refreshBtn = new Button { Left = 280, Top = 10, Width = 110, Height = 28, Text = "Обновить" };
            refreshBtn.Click += async delegate { await LoadDataAsync(); };
            var copyBtn = new Button { Left = 396, Top = 10, Width = 170, Height = 28, Text = "Копировать строку" };
            copyBtn.Click += delegate { CopySelectedRow(); };
            _statusLabel = new Label { Left = 574, Top = 14, Width = 560 };
            _searchBox = new TextBox { Left = 12, Top = 44, Width = 320 };
            _searchBox.TextChanged += delegate { ApplyFilter(); };

            top.Controls.Add(title);
            top.Controls.Add(refreshBtn);
            top.Controls.Add(copyBtn);
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
            };
            if (_canAdmin)
            {
                _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Сотрудник", DataPropertyName = "EmployeeFullName", Width = 200 });
            }
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Предмет", DataPropertyName = "Name", Width = 240 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Инв. номер", DataPropertyName = "InventoryNumber", Width = 120 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Тип", DataPropertyName = "Type", Width = 120 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Каб.", DataPropertyName = "Room", Width = 90 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Дата закрепления", DataPropertyName = "DateIn", Width = 160 });

            Controls.Add(_grid);
            Controls.Add(top);

            Load += async delegate { await LoadDataAsync(); };
        }

        private async Task LoadDataAsync()
        {
            try
            {
                _statusLabel.Text = "Загрузка...";
                var response = _canAdmin
                    ? await _apiClient.InventoryManageAsync(_token)
                    : await _apiClient.InventoryMyAsync(_token);
                _items = response?.Items ?? new List<InventoryItemDto>();
                ApplyFilter();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private void ApplyFilter()
        {
            var term = (_searchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
            var filtered = _items.Where(x =>
                term == string.Empty
                || (x.EmployeeFullName ?? string.Empty).ToLowerInvariant().Contains(term)
                || (x.Name ?? string.Empty).ToLowerInvariant().Contains(term)
                || (x.InventoryNumber ?? string.Empty).ToLowerInvariant().Contains(term)
                || (x.Type ?? string.Empty).ToLowerInvariant().Contains(term)
                || (x.Room ?? string.Empty).ToLowerInvariant().Contains(term)
            ).ToList();

            _grid.DataSource = new BindingSource
            {
                DataSource = filtered.Select(x => new
                {
                    x.EmployeeFullName,
                    x.Name,
                    x.InventoryNumber,
                    x.Type,
                    x.Room,
                    x.DateIn,
                }).ToList()
            };
            _statusLabel.Text = "Активных позиций: " + filtered.Count + " из " + _items.Count;
        }

        private void CopySelectedRow()
        {
            if (_grid.CurrentRow == null)
            {
                _statusLabel.Text = "Выберите строку для копирования.";
                return;
            }

            var values = _grid.CurrentRow.Cells.Cast<DataGridViewCell>().Select(c => Convert.ToString(c.Value) ?? string.Empty);
            var text = string.Join(" | ", values);
            if (string.IsNullOrWhiteSpace(text))
            {
                _statusLabel.Text = "Нет данных для копирования.";
                return;
            }

            if (ClipboardService.TrySetText(text, out var error))
            {
                _statusLabel.Text = "Строка скопирована в буфер обмена.";
                return;
            }

            _statusLabel.Text = error;
        }
    }
}
