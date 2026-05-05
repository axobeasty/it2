using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Runtime.Serialization.Json;
using System.Text;
using System.Threading.Tasks;
using ItMaster.Desktop.Wpf.Models;

namespace ItMaster.Desktop.Wpf.Infrastructure.Api;

public class ApiClient
{
    private readonly HttpClient _httpClient;
    public string BaseUrl { get; }

    public ApiClient(string baseUrl)
    {
        if (string.IsNullOrWhiteSpace(baseUrl))
        {
            throw new ArgumentException("Base URL is required.", nameof(baseUrl));
        }

        _httpClient = new HttpClient
        {
            BaseAddress = new Uri(baseUrl, UriKind.Absolute),
            Timeout = TimeSpan.FromSeconds(25),
        };
        BaseUrl = _httpClient.BaseAddress.ToString();
    }

    public async Task<LoginResponse> LoginAsync(string login, string password)
    {
        var body = "{\"login\":\"" + EscapeJson(login) + "\",\"password\":\"" + EscapeJson(password) + "\"}";
        using var content = new StringContent(body, Encoding.UTF8, "application/json");
        using var response = await _httpClient.PostAsync("login", content);
        var json = await response.Content.ReadAsStringAsync();
        Ensure(response.IsSuccessStatusCode, "Login failed", json);
        return Deserialize<LoginResponse>(json);
    }

    public async Task<UserDto> MeAsync(string token)
    {
        using var request = CreateAuthRequest(HttpMethod.Get, "me", token);
        using var response = await _httpClient.SendAsync(request);
        var json = await response.Content.ReadAsStringAsync();
        Ensure(response.IsSuccessStatusCode, "Session check failed", json);
        return Deserialize<MeResponse>(json).User;
    }

    public async Task<ScheduleResponse> ScheduleAsync(string token, string weekStart = null)
    {
        var path = "schedule";
        if (!string.IsNullOrWhiteSpace(weekStart))
        {
            path += "?week=" + Uri.EscapeDataString(weekStart);
        }
        return await GetAsync<ScheduleResponse>(path, token, "Schedule request failed");
    }

    public Task<TestsListResponse> TestsListAsync(string token) => GetAsync<TestsListResponse>("tests", token, "Tests list request failed");

    public async Task<TestSessionResponse> TestBeginAsync(string token, int testId)
    {
        using var request = CreateAuthRequest(HttpMethod.Post, "tests/" + testId + "/session", token);
        request.Content = new StringContent("{}", Encoding.UTF8, "application/json");
        using var response = await _httpClient.SendAsync(request);
        var json = await response.Content.ReadAsStringAsync();
        Ensure(response.IsSuccessStatusCode, "Test session start failed", json);
        return Deserialize<TestSessionResponse>(json);
    }

    public async Task<TestSubmitResponse> TestSubmitAsync(string token, int testId, string answersJson)
    {
        var payload = "{\"answers\":" + (string.IsNullOrWhiteSpace(answersJson) ? "{}" : answersJson) + "}";
        using var request = CreateAuthRequest(HttpMethod.Post, "tests/" + testId + "/submit", token);
        request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
        using var response = await _httpClient.SendAsync(request);
        var json = await response.Content.ReadAsStringAsync();
        Ensure(response.IsSuccessStatusCode, "Test submit failed", json);
        return Deserialize<TestSubmitResponse>(json);
    }

    public async Task<int> NotificationsBootstrapAsync(string token)
    {
        var response = await GetAsync<NotificationsBootstrapResponse>("notifications?bootstrap=1", token, "Notifications bootstrap failed");
        return response.MaxId;
    }

    public Task<NotificationsResponse> NotificationsAsync(string token, int sinceId) => GetAsync<NotificationsResponse>("notifications?since_id=" + sinceId, token, "Notifications request failed");
    public Task<TestStatsResponse> TestStatsAsync(string token, int groupId = 0, int page = 1) => GetAsync<TestStatsResponse>("test-stats?group_id=" + groupId + "&page=" + page, token, "Test stats request failed");
    public Task<WikiListResponse> WikiListAsync(string token) => GetAsync<WikiListResponse>("wiki", token, "Wiki list request failed");
    public Task<WikiShowResponse> WikiShowAsync(string token, string slug) => GetAsync<WikiShowResponse>("wiki/" + Uri.EscapeDataString(slug ?? string.Empty), token, "Wiki show request failed");
    public Task<OrdersCategoriesResponse> OrdersCategoriesAsync(string token) => GetAsync<OrdersCategoriesResponse>("orders/categories", token, "Orders categories request failed");
    public Task<OrdersListResponse> OrdersMyAsync(string token) => GetAsync<OrdersListResponse>("orders/my", token, "Orders list request failed");
    public Task<InventoryResponse> InventoryMyAsync(string token) => GetAsync<InventoryResponse>("inventory/my", token, "Inventory my request failed");
    public Task<InventoryResponse> InventoryManageAsync(string token) => GetAsync<InventoryResponse>("inventory/manage", token, "Inventory manage request failed");
    public Task<EmployeesResponse> EmployeesAsync(string token) => GetAsync<EmployeesResponse>("employees", token, "Employees request failed");
    public Task<RolesResponse> RolesAsync(string token) => GetAsync<RolesResponse>("roles", token, "Roles request failed");
    public Task<GroupsResponse> GroupsAsync(string token) => GetAsync<GroupsResponse>("groups", token, "Groups request failed");
    public Task<SettingsGeneralResponse> SettingsGeneralAsync(string token) => GetAsync<SettingsGeneralResponse>("settings/general", token, "Settings request failed");
    public Task<RolePermissionsResponse> RolesPermissionsAsync(string token, int id) => GetAsync<RolePermissionsResponse>("roles/" + id + "/permissions", token, "Role permissions request failed");

    public Task<OrderCreateResponse> OrdersCreateAsync(string token, string description, int categoryId, string room) =>
        SendJsonAsync<OrderCreateResponse>(HttpMethod.Post, "orders/create", token, "{\"description\":\"" + EscapeJson(description) + "\",\"category_id\":" + categoryId + ",\"room\":\"" + EscapeJson(room ?? string.Empty) + "\"}", "Orders create request failed");
    public Task<OrderStatusResponse> OrdersSetStatusAsync(string token, int orderId, int statusCode) =>
        SendJsonAsync<OrderStatusResponse>(HttpMethod.Patch, "orders/" + orderId + "/status/" + statusCode, token, "{}", "Orders status update failed");
    public Task<EmployeeStatusResponse> EmployeesSetActiveAsync(string token, int employeeId, bool active) =>
        SendJsonAsync<EmployeeStatusResponse>(HttpMethod.Patch, "employees/" + employeeId + "/active/" + (active ? 1 : 0), token, "{}", "Employee active update failed");
    public Task<GenericMessageResponse> EmployeesAssignAsync(string token, int employeeId, int roleId, int groupId) =>
        SendJsonAsync<GenericMessageResponse>(HttpMethod.Patch, "employees/" + employeeId + "/assign", token, "{\"role_id\":" + roleId + ",\"group_id\":" + groupId + "}", "Employee assign update failed");
    public Task<GenericMessageResponse> RolesCreateAsync(string token, string name) =>
        SendJsonAsync<GenericMessageResponse>(HttpMethod.Post, "roles", token, "{\"name\":\"" + EscapeJson(name) + "\"}", "Role create failed");
    public Task<GenericMessageResponse> RolesUpdateAsync(string token, int id, string name) =>
        SendJsonAsync<GenericMessageResponse>(HttpMethod.Patch, "roles/" + id, token, "{\"name\":\"" + EscapeJson(name) + "\"}", "Role update failed");
    public Task<GenericMessageResponse> RolesDeleteAsync(string token, int id) =>
        SendJsonAsync<GenericMessageResponse>(HttpMethod.Delete, "roles/" + id, token, null, "Role delete failed");
    public Task<GenericMessageResponse> RolesPermissionsSaveAsync(string token, int id, IEnumerable<string> permissions)
    {
        var keys = (permissions ?? Array.Empty<string>()).Where(p => !string.IsNullOrWhiteSpace(p)).Select(p => "\"" + EscapeJson(p.Trim()) + "\"");
        return SendJsonAsync<GenericMessageResponse>(HttpMethod.Post, "roles/" + id + "/permissions", token, "{\"permissions\":[" + string.Join(",", keys) + "]}", "Role permissions save failed");
    }
    public Task<GenericMessageResponse> GroupsCreateAsync(string token, string name, string description) =>
        SendJsonAsync<GenericMessageResponse>(HttpMethod.Post, "groups", token, "{\"name\":\"" + EscapeJson(name) + "\",\"description\":\"" + EscapeJson(description ?? string.Empty) + "\"}", "Group create failed");
    public Task<GenericMessageResponse> GroupsUpdateAsync(string token, int id, string name, string description) =>
        SendJsonAsync<GenericMessageResponse>(HttpMethod.Patch, "groups/" + id, token, "{\"name\":\"" + EscapeJson(name) + "\",\"description\":\"" + EscapeJson(description ?? string.Empty) + "\"}", "Group update failed");
    public Task<GenericMessageResponse> GroupsDeleteAsync(string token, int id) =>
        SendJsonAsync<GenericMessageResponse>(HttpMethod.Delete, "groups/" + id, token, null, "Group delete failed");
    public Task<GenericMessageResponse> SettingsGeneralSaveAsync(string token, string title, string disableReason, bool isEnabled) =>
        SendJsonAsync<GenericMessageResponse>(HttpMethod.Post, "settings/general", token, "{\"title\":\"" + EscapeJson(title) + "\",\"disable_reason\":\"" + EscapeJson(disableReason ?? string.Empty) + "\",\"is_enabled\":" + (isEnabled ? "true" : "false") + "}", "Settings save failed");

    private async Task<T> GetAsync<T>(string path, string token, string errorPrefix)
    {
        using var request = CreateAuthRequest(HttpMethod.Get, path, token);
        using var response = await _httpClient.SendAsync(request);
        var json = await response.Content.ReadAsStringAsync();
        Ensure(response.IsSuccessStatusCode, errorPrefix, json);
        return Deserialize<T>(json);
    }

    private async Task<T> SendJsonAsync<T>(HttpMethod method, string path, string token, string payload, string errorPrefix)
    {
        using var request = CreateAuthRequest(method, path, token);
        if (payload != null)
        {
            request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
        }
        using var response = await _httpClient.SendAsync(request);
        var json = await response.Content.ReadAsStringAsync();
        Ensure(response.IsSuccessStatusCode, errorPrefix, json);
        return Deserialize<T>(json);
    }

    private static HttpRequestMessage CreateAuthRequest(HttpMethod method, string path, string token)
    {
        var request = new HttpRequestMessage(method, path);
        request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
        return request;
    }

    private static T Deserialize<T>(string json)
    {
        var serializer = new DataContractJsonSerializer(typeof(T));
        using var stream = new MemoryStream(Encoding.UTF8.GetBytes(json));
        return (T)serializer.ReadObject(stream);
    }

    private static string EscapeJson(string value) => (value ?? string.Empty).Replace("\\", "\\\\").Replace("\"", "\\\"");

    private static void Ensure(bool condition, string prefix, string json)
    {
        if (!condition)
        {
            throw new InvalidOperationException(prefix + ": " + json);
        }
    }
}
