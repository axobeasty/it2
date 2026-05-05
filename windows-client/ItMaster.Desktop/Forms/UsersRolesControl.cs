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
    public class UsersRolesControl : UserControl
    {
        private readonly ApiClient _apiClient;
        private readonly string _token;
        private readonly Label _statusLabel;
        private readonly TabControl _tabs;
        private readonly DataGridView _employeesGrid;
        private readonly DataGridView _rolesGrid;
        private readonly DataGridView _groupsGrid;
        private readonly TextBox _searchBox;
        private readonly TextBox _groupNameBox;
        private readonly TextBox _groupDescriptionBox;
        private readonly TextBox _roleNameBox;
        private readonly CheckedListBox _rolePermissionsList;
        private readonly ComboBox _employeeRoleBox;
        private readonly ComboBox _employeeGroupBox;
        private List<EmployeeListItemDto> _employees = new List<EmployeeListItemDto>();
        private List<RoleListItemDto> _roles = new List<RoleListItemDto>();
        private List<GroupListItemDto> _groups = new List<GroupListItemDto>();

        public UsersRolesControl(ApiClient apiClient, string token)
        {
            _apiClient = apiClient;
            _token = token;
            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var top = new Panel { Dock = DockStyle.Top, Height = 52 };
            var title = new Label
            {
                Text = "Пользователи и роли",
                Left = 12,
                Top = 10,
                Width = 260,
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
            };
            var refresh = new Button { Left = 280, Top = 10, Width = 110, Height = 28, Text = "Обновить" };
            refresh.Click += async delegate { await LoadAllAsync(); };
            var activateBtn = new Button { Left = 400, Top = 10, Width = 120, Height = 28, Text = "Активировать" };
            activateBtn.Click += async delegate { await SetActiveAsync(true); };
            var deactivateBtn = new Button { Left = 526, Top = 10, Width = 120, Height = 28, Text = "Деактивировать" };
            deactivateBtn.Click += async delegate { await SetActiveAsync(false); };
            _statusLabel = new Label { Left = 660, Top = 14, Width = 540 };
            _searchBox = new TextBox { Left = 12, Top = 44, Width = 320 };
            _searchBox.TextChanged += delegate { ApplyFilter(); };
            top.Height = 80;

            top.Controls.Add(title);
            top.Controls.Add(refresh);
            top.Controls.Add(activateBtn);
            top.Controls.Add(deactivateBtn);
            top.Controls.Add(_searchBox);
            top.Controls.Add(_statusLabel);

            _tabs = new TabControl { Dock = DockStyle.Fill };
            var tabEmployees = new TabPage("Сотрудники");
            var tabRoles = new TabPage("Роли");
            var tabGroups = new TabPage("Группы");

            var employeeTop = new Panel { Dock = DockStyle.Top, Height = 58 };
            _employeeRoleBox = new ComboBox { Left = 8, Top = 16, Width = 220, DropDownStyle = ComboBoxStyle.DropDownList };
            _employeeGroupBox = new ComboBox { Left = 236, Top = 16, Width = 220, DropDownStyle = ComboBoxStyle.DropDownList };
            var employeeAssignBtn = new Button { Left = 464, Top = 14, Width = 150, Height = 28, Text = "Сохранить роль/группу" };
            employeeAssignBtn.Click += async delegate { await AssignEmployeeAsync(); };
            employeeTop.Controls.Add(_employeeRoleBox);
            employeeTop.Controls.Add(_employeeGroupBox);
            employeeTop.Controls.Add(employeeAssignBtn);

            _employeesGrid = new DataGridView { Dock = DockStyle.Fill, ReadOnly = true, AutoGenerateColumns = false, AllowUserToAddRows = false, AllowUserToDeleteRows = false, SelectionMode = DataGridViewSelectionMode.FullRowSelect };
            _employeesGrid.MultiSelect = true;
            _employeesGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "ID", DataPropertyName = "Id", Width = 60 });
            _employeesGrid.Columns.Add(new DataGridViewCheckBoxColumn { HeaderText = "Активен", DataPropertyName = "Active", Width = 70 });
            _employeesGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "ФИО", DataPropertyName = "FullName", Width = 220 });
            _employeesGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Логин", DataPropertyName = "Login", Width = 120 });
            _employeesGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Email", DataPropertyName = "Email", Width = 220 });
            _employeesGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Роль", DataPropertyName = "RoleName", Width = 140 });
            _employeesGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Группа", DataPropertyName = "GroupName", Width = 130 });
            _employeesGrid.SelectionChanged += EmployeesGrid_SelectionChanged;

            var roleTop = new Panel { Dock = DockStyle.Top, Height = 92 };
            _roleNameBox = new TextBox { Left = 8, Top = 16, Width = 260 };
            var roleCreateBtn = new Button { Left = 276, Top = 14, Width = 90, Height = 28, Text = "Создать" };
            var roleUpdateBtn = new Button { Left = 372, Top = 14, Width = 90, Height = 28, Text = "Изменить" };
            var roleDeleteBtn = new Button { Left = 468, Top = 14, Width = 90, Height = 28, Text = "Удалить" };
            var rolePermissionsSaveBtn = new Button { Left = 564, Top = 14, Width = 170, Height = 28, Text = "Сохранить права" };
            roleCreateBtn.Click += async delegate { await RoleCreateAsync(); };
            roleUpdateBtn.Click += async delegate { await RoleUpdateAsync(); };
            roleDeleteBtn.Click += async delegate { await RoleDeleteAsync(); };
            rolePermissionsSaveBtn.Click += async delegate { await SaveRolePermissionsAsync(); };
            roleTop.Controls.Add(_roleNameBox);
            roleTop.Controls.Add(roleCreateBtn);
            roleTop.Controls.Add(roleUpdateBtn);
            roleTop.Controls.Add(roleDeleteBtn);
            roleTop.Controls.Add(rolePermissionsSaveBtn);

            _rolePermissionsList = new CheckedListBox
            {
                Left = 8,
                Top = 48,
                Width = 726,
                Height = 38,
                CheckOnClick = true,
                MultiColumn = true,
                ColumnWidth = 240,
                BorderStyle = BorderStyle.FixedSingle,
            };
            roleTop.Controls.Add(_rolePermissionsList);

            _rolesGrid = new DataGridView { Dock = DockStyle.Fill, ReadOnly = true, AutoGenerateColumns = false, AllowUserToAddRows = false, AllowUserToDeleteRows = false, SelectionMode = DataGridViewSelectionMode.FullRowSelect };
            _rolesGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "ID", DataPropertyName = "Id", Width = 60 });
            _rolesGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Название", DataPropertyName = "Name", Width = 280 });
            _rolesGrid.Columns.Add(new DataGridViewCheckBoxColumn { HeaderText = "Системная", DataPropertyName = "IsSystem", Width = 80 });
            _rolesGrid.SelectionChanged += RolesGrid_SelectionChanged;

            var groupTop = new Panel { Dock = DockStyle.Top, Height = 58 };
            _groupNameBox = new TextBox { Left = 8, Top = 16, Width = 180 };
            _groupDescriptionBox = new TextBox { Left = 196, Top = 16, Width = 280 };
            var groupCreateBtn = new Button { Left = 484, Top = 14, Width = 90, Height = 28, Text = "Создать" };
            var groupUpdateBtn = new Button { Left = 580, Top = 14, Width = 90, Height = 28, Text = "Изменить" };
            var groupDeleteBtn = new Button { Left = 676, Top = 14, Width = 90, Height = 28, Text = "Удалить" };
            groupCreateBtn.Click += async delegate { await GroupCreateAsync(); };
            groupUpdateBtn.Click += async delegate { await GroupUpdateAsync(); };
            groupDeleteBtn.Click += async delegate { await GroupDeleteAsync(); };
            groupTop.Controls.Add(_groupNameBox);
            groupTop.Controls.Add(_groupDescriptionBox);
            groupTop.Controls.Add(groupCreateBtn);
            groupTop.Controls.Add(groupUpdateBtn);
            groupTop.Controls.Add(groupDeleteBtn);

            _groupsGrid = new DataGridView { Dock = DockStyle.Fill, ReadOnly = true, AutoGenerateColumns = false, AllowUserToAddRows = false, AllowUserToDeleteRows = false, SelectionMode = DataGridViewSelectionMode.FullRowSelect };
            _groupsGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "ID", DataPropertyName = "Id", Width = 60 });
            _groupsGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Название", DataPropertyName = "Name", Width = 220 });
            _groupsGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Описание", DataPropertyName = "Description", Width = 380 });
            _groupsGrid.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Студентов", DataPropertyName = "StudentsCount", Width = 90 });
            _groupsGrid.SelectionChanged += GroupsGrid_SelectionChanged;

            tabEmployees.Controls.Add(employeeTop);
            tabEmployees.Controls.Add(_employeesGrid);
            tabRoles.Controls.Add(roleTop);
            tabRoles.Controls.Add(_rolesGrid);
            tabGroups.Controls.Add(groupTop);
            tabGroups.Controls.Add(_groupsGrid);
            _tabs.TabPages.Add(tabEmployees);
            _tabs.TabPages.Add(tabRoles);
            _tabs.TabPages.Add(tabGroups);

            Controls.Add(_tabs);
            Controls.Add(top);

            Load += async delegate { await LoadAllAsync(); };
        }

        private async Task LoadAllAsync()
        {
            try
            {
                var employees = await _apiClient.EmployeesAsync(_token);
                _employees = employees?.Items ?? new List<EmployeeListItemDto>();

                var roles = await _apiClient.RolesAsync(_token);
                _roles = roles?.Items ?? new List<RoleListItemDto>();

                var groups = await _apiClient.GroupsAsync(_token);
                _groups = groups?.Items ?? new List<GroupListItemDto>();

                BindEmployeeSelectors();
                ApplyFilter();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private void GroupsGrid_SelectionChanged(object sender, EventArgs e)
        {
            var selected = GetSelectedGroup();
            if (selected == null)
            {
                return;
            }

            _groupNameBox.Text = selected.Name ?? string.Empty;
            _groupDescriptionBox.Text = selected.Description ?? string.Empty;
        }

        private void EmployeesGrid_SelectionChanged(object sender, EventArgs e)
        {
            var selected = GetSelectedEmployee();
            if (selected == null)
            {
                return;
            }

            SelectComboById(_employeeRoleBox, selected.RoleId);
            SelectComboById(_employeeGroupBox, selected.GroupId);
        }

        private void RolesGrid_SelectionChanged(object sender, EventArgs e)
        {
            var selected = GetSelectedRole();
            if (selected == null)
            {
                return;
            }

            _roleNameBox.Text = selected.Name ?? string.Empty;
            _ = LoadRolePermissionsAsync(selected);
        }

        private RoleListItemDto GetSelectedRole()
        {
            if (_rolesGrid.CurrentRow == null)
            {
                return null;
            }

            var idCell = _rolesGrid.CurrentRow.Cells.Count > 0 ? _rolesGrid.CurrentRow.Cells[0].Value : null;
            if (idCell == null)
            {
                return null;
            }
            if (!int.TryParse(idCell.ToString(), out var id))
            {
                return null;
            }

            return _roles.FirstOrDefault(r => r.Id == id);
        }

        private GroupListItemDto GetSelectedGroup()
        {
            if (_groupsGrid.CurrentRow == null)
            {
                return null;
            }

            var idCell = _groupsGrid.CurrentRow.Cells.Count > 0 ? _groupsGrid.CurrentRow.Cells[0].Value : null;
            if (idCell == null)
            {
                return null;
            }

            if (!int.TryParse(idCell.ToString(), out var id))
            {
                return null;
            }

            return _groups.FirstOrDefault(g => g.Id == id);
        }

        private EmployeeListItemDto GetSelectedEmployee()
        {
            if (_employeesGrid.CurrentRow == null)
            {
                return null;
            }

            var idCell = _employeesGrid.CurrentRow.Cells.Count > 0 ? _employeesGrid.CurrentRow.Cells[0].Value : null;
            if (idCell == null)
            {
                return null;
            }
            if (!int.TryParse(idCell.ToString(), out var id))
            {
                return null;
            }

            return _employees.FirstOrDefault(e => e.Id == id);
        }

        private void BindEmployeeSelectors()
        {
            _employeeRoleBox.DataSource = null;
            _employeeGroupBox.DataSource = null;

            var roleOptions = _roles.Select(r => new IdNameOption { Id = r.Id, Name = r.Name }).ToList();
            var groupOptions = _groups.Select(g => new IdNameOption { Id = g.Id, Name = g.Name }).ToList();

            _employeeRoleBox.DataSource = roleOptions;
            _employeeRoleBox.DisplayMember = "Name";
            _employeeRoleBox.ValueMember = "Id";

            _employeeGroupBox.DataSource = groupOptions;
            _employeeGroupBox.DisplayMember = "Name";
            _employeeGroupBox.ValueMember = "Id";
        }

        private static void SelectComboById(ComboBox combo, int id)
        {
            if (combo.Items.Count == 0)
            {
                return;
            }

            for (var i = 0; i < combo.Items.Count; i++)
            {
                var option = combo.Items[i] as IdNameOption;
                if (option != null && option.Id == id)
                {
                    combo.SelectedIndex = i;
                    return;
                }
            }

            combo.SelectedIndex = 0;
        }

        private async Task GroupCreateAsync()
        {
            if (string.IsNullOrWhiteSpace(_groupNameBox.Text))
            {
                _statusLabel.Text = "Введите название группы.";
                return;
            }

            try
            {
                var response = await _apiClient.GroupsCreateAsync(_token, _groupNameBox.Text.Trim(), _groupDescriptionBox.Text.Trim());
                _statusLabel.Text = response?.Message ?? "Группа создана.";
                await LoadAllAsync();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task GroupUpdateAsync()
        {
            var selected = GetSelectedGroup();
            if (selected == null)
            {
                _statusLabel.Text = "Выберите группу для изменения.";
                return;
            }
            if (string.IsNullOrWhiteSpace(_groupNameBox.Text))
            {
                _statusLabel.Text = "Введите название группы.";
                return;
            }

            try
            {
                var response = await _apiClient.GroupsUpdateAsync(_token, selected.Id, _groupNameBox.Text.Trim(), _groupDescriptionBox.Text.Trim());
                _statusLabel.Text = response?.Message ?? "Группа обновлена.";
                await LoadAllAsync();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task GroupDeleteAsync()
        {
            var selected = GetSelectedGroup();
            if (selected == null)
            {
                _statusLabel.Text = "Выберите группу для удаления.";
                return;
            }

            var confirm = MessageBox.Show(
                "Удалить группу \"" + selected.Name + "\"? Студенты будут откреплены.",
                "Подтверждение",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Warning);
            if (confirm != DialogResult.Yes)
            {
                return;
            }

            try
            {
                var response = await _apiClient.GroupsDeleteAsync(_token, selected.Id);
                _statusLabel.Text = response?.Message ?? "Группа удалена.";
                await LoadAllAsync();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task RoleCreateAsync()
        {
            if (string.IsNullOrWhiteSpace(_roleNameBox.Text))
            {
                _statusLabel.Text = "Введите название роли.";
                return;
            }

            try
            {
                var response = await _apiClient.RolesCreateAsync(_token, _roleNameBox.Text.Trim());
                _statusLabel.Text = response?.Message ?? "Роль создана.";
                await LoadAllAsync();
                SelectRoleByName(_roleNameBox.Text.Trim());
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task RoleUpdateAsync()
        {
            var selected = GetSelectedRole();
            if (selected == null)
            {
                _statusLabel.Text = "Выберите роль для изменения.";
                return;
            }
            if (selected.IsSystem)
            {
                _statusLabel.Text = "Системную роль нельзя изменить.";
                return;
            }
            if (string.IsNullOrWhiteSpace(_roleNameBox.Text))
            {
                _statusLabel.Text = "Введите название роли.";
                return;
            }

            try
            {
                var response = await _apiClient.RolesUpdateAsync(_token, selected.Id, _roleNameBox.Text.Trim());
                _statusLabel.Text = response?.Message ?? "Роль обновлена.";
                await LoadAllAsync();
                SelectRoleById(selected.Id);
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task RoleDeleteAsync()
        {
            var selected = GetSelectedRole();
            if (selected == null)
            {
                _statusLabel.Text = "Выберите роль для удаления.";
                return;
            }
            if (selected.IsSystem)
            {
                _statusLabel.Text = "Системную роль нельзя удалить.";
                return;
            }

            var confirm = MessageBox.Show(
                "Удалить роль \"" + selected.Name + "\"?",
                "Подтверждение",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Warning);
            if (confirm != DialogResult.Yes)
            {
                return;
            }

            try
            {
                var response = await _apiClient.RolesDeleteAsync(_token, selected.Id);
                _statusLabel.Text = response?.Message ?? "Роль удалена.";
                await LoadAllAsync();
                ClearRolePermissionsUi();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task AssignEmployeeAsync()
        {
            var selected = GetSelectedEmployee();
            if (selected == null)
            {
                _statusLabel.Text = "Выберите сотрудника.";
                return;
            }
            if (_employeeRoleBox.SelectedItem == null || _employeeGroupBox.SelectedItem == null)
            {
                _statusLabel.Text = "Выберите роль и группу.";
                return;
            }

            var roleOption = _employeeRoleBox.SelectedItem as IdNameOption;
            var groupOption = _employeeGroupBox.SelectedItem as IdNameOption;
            if (roleOption == null || groupOption == null)
            {
                _statusLabel.Text = "Некорректные значения роли или группы.";
                return;
            }

            try
            {
                var response = await _apiClient.EmployeesAssignAsync(_token, selected.Id, roleOption.Id, groupOption.Id);
                _statusLabel.Text = response?.Message ?? "Назначения сотрудника обновлены.";
                await LoadAllAsync();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task LoadRolePermissionsAsync(RoleListItemDto role)
        {
            if (role == null)
            {
                ClearRolePermissionsUi();
                return;
            }

            try
            {
                var response = await _apiClient.RolesPermissionsAsync(_token, role.Id);
                BindRolePermissions(response);
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private void BindRolePermissions(RolePermissionsResponse response)
        {
            _rolePermissionsList.Items.Clear();
            var selected = new HashSet<string>(response?.Selected ?? new List<string>(), StringComparer.OrdinalIgnoreCase);
            var options = response?.Options ?? new List<RolePermissionOptionDto>();
            foreach (var option in options)
            {
                var item = new PermissionItem
                {
                    Key = option.Key ?? string.Empty,
                    Label = option.Label ?? option.Key ?? string.Empty,
                };
                _rolePermissionsList.Items.Add(item, selected.Contains(item.Key));
            }
        }

        private async Task SaveRolePermissionsAsync()
        {
            var role = GetSelectedRole();
            if (role == null)
            {
                _statusLabel.Text = "Выберите роль.";
                return;
            }
            if (role.IsSystem)
            {
                _statusLabel.Text = "Системной роли нельзя менять права.";
                return;
            }

            var keys = new List<string>();
            foreach (var checkedItem in _rolePermissionsList.CheckedItems)
            {
                var permission = checkedItem as PermissionItem;
                if (permission != null && !string.IsNullOrWhiteSpace(permission.Key))
                {
                    keys.Add(permission.Key);
                }
            }

            try
            {
                var response = await _apiClient.RolesPermissionsSaveAsync(_token, role.Id, keys);
                _statusLabel.Text = response?.Message ?? "Права роли обновлены.";
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private async Task SetActiveAsync(bool active)
        {
            if (_tabs.SelectedTab == null || _tabs.SelectedTab.Text != "Сотрудники")
            {
                _statusLabel.Text = "Смена статуса доступна на вкладке «Сотрудники».";
                return;
            }
            var selectedIds = GetSelectedEmployeeIds();
            if (selectedIds.Count == 0)
            {
                _statusLabel.Text = "Выберите сотрудника.";
                return;
            }

            try
            {
                var changed = 0;
                var firstMessage = string.Empty;
                foreach (var id in selectedIds)
                {
                    var response = await _apiClient.EmployeesSetActiveAsync(_token, id, active);
                    if (string.IsNullOrWhiteSpace(firstMessage))
                    {
                        firstMessage = response?.Message ?? string.Empty;
                    }
                    changed++;
                }

                _statusLabel.Text = (string.IsNullOrWhiteSpace(firstMessage) ? "Статус обновлен." : firstMessage) + " Изменено: " + changed;
                await LoadAllAsync();
            }
            catch (Exception ex)
            {
                _statusLabel.Text = ex.Message;
            }
        }

        private List<int> GetSelectedEmployeeIds()
        {
            var ids = new List<int>();
            foreach (DataGridViewRow row in _employeesGrid.SelectedRows)
            {
                if (row == null || row.Cells.Count == 0)
                {
                    continue;
                }
                var value = row.Cells[0].Value;
                if (value == null)
                {
                    continue;
                }
                if (int.TryParse(value.ToString(), out var id))
                {
                    ids.Add(id);
                }
            }

            if (ids.Count == 0)
            {
                var selected = GetSelectedEmployee();
                if (selected != null)
                {
                    ids.Add(selected.Id);
                }
            }

            return ids.Distinct().ToList();
        }

        private void ApplyFilter()
        {
            var term = (_searchBox.Text ?? string.Empty).Trim().ToLowerInvariant();
            var employees = _employees.Where(e =>
                term == string.Empty
                || (e.FullName ?? string.Empty).ToLowerInvariant().Contains(term)
                || (e.Login ?? string.Empty).ToLowerInvariant().Contains(term)
                || (e.Email ?? string.Empty).ToLowerInvariant().Contains(term)
                || (e.RoleName ?? string.Empty).ToLowerInvariant().Contains(term)
            ).ToList();
            _employeesGrid.DataSource = new BindingSource
            {
                DataSource = employees.Select(e => new { e.Id, e.Active, e.FullName, e.Login, e.Email, e.RoleName, e.GroupName }).ToList()
            };

            var roles = _roles.Where(r => term == string.Empty || (r.Name ?? string.Empty).ToLowerInvariant().Contains(term)).ToList();
            _rolesGrid.DataSource = new BindingSource
            {
                DataSource = roles.Select(r => new { r.Id, r.Name, r.IsSystem }).ToList()
            };

            var groups = _groups.Where(g =>
                term == string.Empty
                || (g.Name ?? string.Empty).ToLowerInvariant().Contains(term)
                || (g.Description ?? string.Empty).ToLowerInvariant().Contains(term)
            ).ToList();
            _groupsGrid.DataSource = new BindingSource
            {
                DataSource = groups.Select(g => new { g.Id, g.Name, g.Description, g.StudentsCount }).ToList()
            };

            _statusLabel.Text = "Сотрудников: " + employees.Count + " | Ролей: " + roles.Count + " | Групп: " + groups.Count;
        }

        private void SelectRoleById(int id)
        {
            for (var i = 0; i < _rolesGrid.Rows.Count; i++)
            {
                var row = _rolesGrid.Rows[i];
                if (row?.Cells.Count > 0 && int.TryParse(Convert.ToString(row.Cells[0].Value), out var rowId) && rowId == id)
                {
                    _rolesGrid.ClearSelection();
                    row.Selected = true;
                    _rolesGrid.CurrentCell = row.Cells[0];
                    return;
                }
            }
        }

        private void SelectRoleByName(string roleName)
        {
            if (string.IsNullOrWhiteSpace(roleName))
            {
                return;
            }

            var role = _roles.FirstOrDefault(r => string.Equals(r.Name, roleName, StringComparison.OrdinalIgnoreCase));
            if (role != null)
            {
                SelectRoleById(role.Id);
            }
        }

        private void ClearRolePermissionsUi()
        {
            _rolePermissionsList.Items.Clear();
        }

        private sealed class IdNameOption
        {
            public int Id { get; set; }
            public string Name { get; set; }
        }

        private sealed class PermissionItem
        {
            public string Key { get; set; }
            public string Label { get; set; }

            public override string ToString()
            {
                return Label ?? Key ?? string.Empty;
            }
        }
    }
}
