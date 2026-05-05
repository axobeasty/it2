using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Runtime.Serialization.Json;
using System.Text;
using System.Threading.Tasks;
using ItMaster.Desktop.Models;

namespace ItMaster.Desktop.Infrastructure.Api
{
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
            using (var content = new StringContent(body, Encoding.UTF8, "application/json"))
            using (var response = await _httpClient.PostAsync("login", content))
            {
                var json = await response.Content.ReadAsStringAsync();
                if (!response.IsSuccessStatusCode)
                {
                    throw new InvalidOperationException("Login failed: " + json);
                }

                return Deserialize<LoginResponse>(json);
            }
        }

        public async Task<UserDto> MeAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "me"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Session check failed: " + json);
                    }

                    return Deserialize<MeResponse>(json).User;
                }
            }
        }

        public async Task<ScheduleResponse> ScheduleAsync(string token, string weekStart = null)
        {
            var path = "schedule";
            if (!string.IsNullOrWhiteSpace(weekStart))
            {
                path += "?week=" + Uri.EscapeDataString(weekStart);
            }

            using (var request = new HttpRequestMessage(HttpMethod.Get, path))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Schedule request failed: " + json);
                    }

                    return Deserialize<ScheduleResponse>(json);
                }
            }
        }

        public async Task<TestsListResponse> TestsListAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "tests"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Tests list request failed: " + json);
                    }

                    return Deserialize<TestsListResponse>(json);
                }
            }
        }

        public async Task<TestSessionResponse> TestBeginAsync(string token, int testId)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Post, "tests/" + testId + "/session"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent("{}", Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Test session start failed: " + json);
                    }

                    return Deserialize<TestSessionResponse>(json);
                }
            }
        }

        public async Task<TestSubmitResponse> TestSubmitAsync(string token, int testId, string answersJson)
        {
            var payload = "{\"answers\":" + (string.IsNullOrWhiteSpace(answersJson) ? "{}" : answersJson) + "}";
            using (var request = new HttpRequestMessage(HttpMethod.Post, "tests/" + testId + "/submit"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Test submit failed: " + json);
                    }

                    return Deserialize<TestSubmitResponse>(json);
                }
            }
        }

        public async Task<int> NotificationsBootstrapAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "notifications?bootstrap=1"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Notifications bootstrap failed: " + json);
                    }

                    return Deserialize<NotificationsBootstrapResponse>(json).MaxId;
                }
            }
        }

        public async Task<NotificationsResponse> NotificationsAsync(string token, int sinceId)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "notifications?since_id=" + sinceId))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Notifications request failed: " + json);
                    }

                    return Deserialize<NotificationsResponse>(json);
                }
            }
        }

        public async Task<TestStatsResponse> TestStatsAsync(string token, int groupId = 0, int page = 1)
        {
            var path = "test-stats?group_id=" + groupId + "&page=" + page;
            using (var request = new HttpRequestMessage(HttpMethod.Get, path))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Test stats request failed: " + json);
                    }

                    return Deserialize<TestStatsResponse>(json);
                }
            }
        }

        public async Task<WikiListResponse> WikiListAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "wiki"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Wiki list request failed: " + json);
                    }

                    return Deserialize<WikiListResponse>(json);
                }
            }
        }

        public async Task<WikiShowResponse> WikiShowAsync(string token, string slug)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "wiki/" + Uri.EscapeDataString(slug ?? string.Empty)))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Wiki show request failed: " + json);
                    }

                    return Deserialize<WikiShowResponse>(json);
                }
            }
        }

        public async Task<OrdersCategoriesResponse> OrdersCategoriesAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "orders/categories"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Orders categories request failed: " + json);
                    }

                    return Deserialize<OrdersCategoriesResponse>(json);
                }
            }
        }

        public async Task<OrdersListResponse> OrdersMyAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "orders/my"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Orders list request failed: " + json);
                    }

                    return Deserialize<OrdersListResponse>(json);
                }
            }
        }

        public async Task<OrderCreateResponse> OrdersCreateAsync(string token, string description, int categoryId, string room)
        {
            var payload = "{\"description\":\"" + EscapeJson(description) + "\",\"category_id\":" + categoryId + ",\"room\":\"" + EscapeJson(room ?? string.Empty) + "\"}";
            using (var request = new HttpRequestMessage(HttpMethod.Post, "orders/create"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Orders create request failed: " + json);
                    }

                    return Deserialize<OrderCreateResponse>(json);
                }
            }
        }

        public async Task<OrderStatusResponse> OrdersSetStatusAsync(string token, int orderId, int statusCode)
        {
            using (var request = new HttpRequestMessage(new HttpMethod("PATCH"), "orders/" + orderId + "/status/" + statusCode))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent("{}", Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Orders status update failed: " + json);
                    }

                    return Deserialize<OrderStatusResponse>(json);
                }
            }
        }

        public async Task<InventoryResponse> InventoryMyAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "inventory/my"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Inventory my request failed: " + json);
                    }

                    return Deserialize<InventoryResponse>(json);
                }
            }
        }

        public async Task<InventoryResponse> InventoryManageAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "inventory/manage"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Inventory manage request failed: " + json);
                    }

                    return Deserialize<InventoryResponse>(json);
                }
            }
        }

        public async Task<EmployeesResponse> EmployeesAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "employees"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Employees request failed: " + json);
                    }

                    return Deserialize<EmployeesResponse>(json);
                }
            }
        }

        public async Task<EmployeeStatusResponse> EmployeesSetActiveAsync(string token, int employeeId, bool active)
        {
            var state = active ? 1 : 0;
            using (var request = new HttpRequestMessage(new HttpMethod("PATCH"), "employees/" + employeeId + "/active/" + state))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent("{}", Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Employee active update failed: " + json);
                    }

                    return Deserialize<EmployeeStatusResponse>(json);
                }
            }
        }

        public async Task<GenericMessageResponse> EmployeesAssignAsync(string token, int employeeId, int roleId, int groupId)
        {
            var payload = "{\"role_id\":" + roleId + ",\"group_id\":" + groupId + "}";
            using (var request = new HttpRequestMessage(new HttpMethod("PATCH"), "employees/" + employeeId + "/assign"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Employee assign update failed: " + json);
                    }

                    return Deserialize<GenericMessageResponse>(json);
                }
            }
        }

        public async Task<RolesResponse> RolesAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "roles"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Roles request failed: " + json);
                    }

                    return Deserialize<RolesResponse>(json);
                }
            }
        }

        public async Task<GenericMessageResponse> RolesCreateAsync(string token, string name)
        {
            var payload = "{\"name\":\"" + EscapeJson(name) + "\"}";
            using (var request = new HttpRequestMessage(HttpMethod.Post, "roles"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Role create failed: " + json);
                    }

                    return Deserialize<GenericMessageResponse>(json);
                }
            }
        }

        public async Task<GenericMessageResponse> RolesUpdateAsync(string token, int id, string name)
        {
            var payload = "{\"name\":\"" + EscapeJson(name) + "\"}";
            using (var request = new HttpRequestMessage(new HttpMethod("PATCH"), "roles/" + id))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Role update failed: " + json);
                    }

                    return Deserialize<GenericMessageResponse>(json);
                }
            }
        }

        public async Task<GenericMessageResponse> RolesDeleteAsync(string token, int id)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Delete, "roles/" + id))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Role delete failed: " + json);
                    }

                    return Deserialize<GenericMessageResponse>(json);
                }
            }
        }

        public async Task<RolePermissionsResponse> RolesPermissionsAsync(string token, int id)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "roles/" + id + "/permissions"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Role permissions request failed: " + json);
                    }

                    return Deserialize<RolePermissionsResponse>(json);
                }
            }
        }

        public async Task<GenericMessageResponse> RolesPermissionsSaveAsync(string token, int id, IEnumerable<string> permissions)
        {
            var keys = (permissions ?? new string[0]).Where(p => !string.IsNullOrWhiteSpace(p)).Select(p => "\"" + EscapeJson(p.Trim()) + "\"");
            var payload = "{\"permissions\":[" + string.Join(",", keys) + "]}";
            using (var request = new HttpRequestMessage(HttpMethod.Post, "roles/" + id + "/permissions"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Role permissions save failed: " + json);
                    }

                    return Deserialize<GenericMessageResponse>(json);
                }
            }
        }

        public async Task<GroupsResponse> GroupsAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "groups"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Groups request failed: " + json);
                    }

                    return Deserialize<GroupsResponse>(json);
                }
            }
        }

        public async Task<GenericMessageResponse> GroupsCreateAsync(string token, string name, string description)
        {
            var payload = "{\"name\":\"" + EscapeJson(name) + "\",\"description\":\"" + EscapeJson(description ?? string.Empty) + "\"}";
            using (var request = new HttpRequestMessage(HttpMethod.Post, "groups"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Group create failed: " + json);
                    }

                    return Deserialize<GenericMessageResponse>(json);
                }
            }
        }

        public async Task<GenericMessageResponse> GroupsUpdateAsync(string token, int id, string name, string description)
        {
            var payload = "{\"name\":\"" + EscapeJson(name) + "\",\"description\":\"" + EscapeJson(description ?? string.Empty) + "\"}";
            using (var request = new HttpRequestMessage(new HttpMethod("PATCH"), "groups/" + id))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Group update failed: " + json);
                    }

                    return Deserialize<GenericMessageResponse>(json);
                }
            }
        }

        public async Task<GenericMessageResponse> GroupsDeleteAsync(string token, int id)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Delete, "groups/" + id))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Group delete failed: " + json);
                    }

                    return Deserialize<GenericMessageResponse>(json);
                }
            }
        }

        public async Task<SettingsGeneralResponse> SettingsGeneralAsync(string token)
        {
            using (var request = new HttpRequestMessage(HttpMethod.Get, "settings/general"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Settings request failed: " + json);
                    }

                    return Deserialize<SettingsGeneralResponse>(json);
                }
            }
        }

        public async Task<GenericMessageResponse> SettingsGeneralSaveAsync(string token, string title, string disableReason, bool isEnabled)
        {
            var payload = "{\"title\":\"" + EscapeJson(title) + "\",\"disable_reason\":\"" + EscapeJson(disableReason ?? string.Empty) + "\",\"is_enabled\":" + (isEnabled ? "true" : "false") + "}";
            using (var request = new HttpRequestMessage(HttpMethod.Post, "settings/general"))
            {
                request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", token);
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                using (var response = await _httpClient.SendAsync(request))
                {
                    var json = await response.Content.ReadAsStringAsync();
                    if (!response.IsSuccessStatusCode)
                    {
                        throw new InvalidOperationException("Settings save failed: " + json);
                    }

                    return Deserialize<GenericMessageResponse>(json);
                }
            }
        }

        private static T Deserialize<T>(string json)
        {
            var serializer = new DataContractJsonSerializer(typeof(T));
            using (var stream = new MemoryStream(Encoding.UTF8.GetBytes(json)))
            {
                return (T)serializer.ReadObject(stream);
            }
        }

        private static string EscapeJson(string value)
        {
            return (value ?? string.Empty).Replace("\\", "\\\\").Replace("\"", "\\\"");
        }
    }
}
