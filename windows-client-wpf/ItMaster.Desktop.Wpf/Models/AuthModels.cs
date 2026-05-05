using System.Collections.Generic;
using System.Runtime.Serialization;

namespace ItMaster.Desktop.Wpf.Models;

[DataContract]
public class LoginResponse
{
    [DataMember(Name = "token")] public string Token { get; set; } = string.Empty;
    [DataMember(Name = "expires_in")] public int ExpiresIn { get; set; }
    [DataMember(Name = "user")] public UserDto User { get; set; } = new();
}

[DataContract]
public class MeResponse
{
    [DataMember(Name = "user")] public UserDto User { get; set; } = new();
}

[DataContract]
public class UserDto
{
    [DataMember(Name = "id")] public int Id { get; set; }
    [DataMember(Name = "login")] public string Login { get; set; } = string.Empty;
    [DataMember(Name = "fio")] public string FullName { get; set; } = string.Empty;
    [DataMember(Name = "role_name")] public string RoleName { get; set; } = string.Empty;
    [DataMember(Name = "group_name")] public string GroupName { get; set; } = string.Empty;
    [DataMember(Name = "permissions")] public Dictionary<string, bool> Permissions { get; set; } = new();
}

[DataContract] public class ScheduleResponse { [DataMember(Name = "week_start")] public string WeekStart { get; set; } = string.Empty; [DataMember(Name = "group")] public string GroupName { get; set; } = string.Empty; [DataMember(Name = "entries")] public List<ScheduleEntryDto> Entries { get; set; } = new(); }
[DataContract] public class ScheduleEntryDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "weekday_label")] public string WeekdayLabel { get; set; } = string.Empty; [DataMember(Name = "start_time")] public string StartTime { get; set; } = string.Empty; [DataMember(Name = "end_time")] public string EndTime { get; set; } = string.Empty; [DataMember(Name = "subject")] public string Subject { get; set; } = string.Empty; [DataMember(Name = "teacher")] public string Teacher { get; set; } = string.Empty; [DataMember(Name = "room")] public string Room { get; set; } = string.Empty; [DataMember(Name = "building_label")] public string BuildingLabel { get; set; } = string.Empty; }

[DataContract] public class TestsListResponse { [DataMember(Name = "tests")] public List<TestSummaryDto> Tests { get; set; } = new(); }
[DataContract] public class TestSummaryDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "title")] public string Title { get; set; } = string.Empty; [DataMember(Name = "description")] public string Description { get; set; } = string.Empty; [DataMember(Name = "attempts_limit")] public int AttemptsLimit { get; set; } [DataMember(Name = "attempts_used")] public int AttemptsUsed { get; set; } [DataMember(Name = "can_start")] public bool CanStart { get; set; } }
[DataContract] public class TestSessionResponse { [DataMember(Name = "test")] public TestDetailsDto Test { get; set; } = new(); }
[DataContract] public class TestDetailsDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "title")] public string Title { get; set; } = string.Empty; [DataMember(Name = "questions")] public List<TestQuestionDto> Questions { get; set; } = new(); }
[DataContract] public class TestQuestionDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "type")] public string Type { get; set; } = string.Empty; [DataMember(Name = "question_text")] public string QuestionText { get; set; } = string.Empty; [DataMember(Name = "options")] public List<string> Options { get; set; } = new(); [DataMember(Name = "left")] public List<string> Left { get; set; } = new(); [DataMember(Name = "right")] public List<string> Right { get; set; } = new(); }
[DataContract] public class TestSubmitResponse { [DataMember(Name = "message")] public string Message { get; set; } = string.Empty; [DataMember(Name = "attempt")] public TestAttemptDto Attempt { get; set; } = new(); }
[DataContract] public class TestAttemptDto { [DataMember(Name = "grade_label")] public string GradeLabel { get; set; } = string.Empty; }

[DataContract] public class NotificationsBootstrapResponse { [DataMember(Name = "max_id")] public int MaxId { get; set; } }
[DataContract] public class NotificationsResponse { [DataMember(Name = "items")] public List<NotificationItemDto> Items { get; set; } = new(); }
[DataContract] public class NotificationItemDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "title")] public string Title { get; set; } = string.Empty; [DataMember(Name = "message")] public string Message { get; set; } = string.Empty; [DataMember(Name = "is_read")] public bool IsRead { get; set; } [DataMember(Name = "created_at")] public string CreatedAt { get; set; } = string.Empty; }

[DataContract] public class TestStatsResponse { [DataMember(Name = "groups")] public List<GroupOptionDto> Groups { get; set; } = new(); [DataMember(Name = "attempts")] public TestStatsAttemptsDto Attempts { get; set; } = new(); }
[DataContract] public class GroupOptionDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "name")] public string Name { get; set; } = string.Empty; }
[DataContract] public class TestStatsAttemptsDto { [DataMember(Name = "current_page")] public int CurrentPage { get; set; } [DataMember(Name = "last_page")] public int LastPage { get; set; } [DataMember(Name = "total")] public int Total { get; set; } [DataMember(Name = "data")] public List<TestStatsAttemptRowDto> Data { get; set; } = new(); }
[DataContract] public class TestStatsAttemptRowDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "student_fio")] public string StudentFullName { get; set; } = string.Empty; [DataMember(Name = "group_name")] public string GroupName { get; set; } = string.Empty; [DataMember(Name = "test_title")] public string TestTitle { get; set; } = string.Empty; [DataMember(Name = "score")] public int Score { get; set; } [DataMember(Name = "max_score")] public int MaxScore { get; set; } [DataMember(Name = "percentage")] public double Percentage { get; set; } [DataMember(Name = "grade_label")] public string GradeLabel { get; set; } = string.Empty; [DataMember(Name = "submitted_at")] public string SubmittedAt { get; set; } = string.Empty; }

[DataContract] public class WikiListResponse { [DataMember(Name = "items")] public List<WikiPageListItemDto> Items { get; set; } = new(); }
[DataContract] public class WikiPageListItemDto { [DataMember(Name = "title")] public string Title { get; set; } = string.Empty; [DataMember(Name = "slug")] public string Slug { get; set; } = string.Empty; [DataMember(Name = "updated_at")] public string UpdatedAt { get; set; } = string.Empty; }
[DataContract] public class WikiShowResponse { [DataMember(Name = "page")] public WikiPageDetailsDto Page { get; set; } = new(); }
[DataContract] public class WikiPageDetailsDto { [DataMember(Name = "title")] public string Title { get; set; } = string.Empty; [DataMember(Name = "slug")] public string Slug { get; set; } = string.Empty; [DataMember(Name = "body")] public string Body { get; set; } = string.Empty; [DataMember(Name = "updated_at")] public string UpdatedAt { get; set; } = string.Empty; [DataMember(Name = "updated_by")] public string UpdatedBy { get; set; } = string.Empty; }

[DataContract] public class OrdersCategoriesResponse { [DataMember(Name = "items")] public List<OrderCategoryDto> Items { get; set; } = new(); }
[DataContract] public class OrderCategoryDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "name")] public string Name { get; set; } = string.Empty; }
[DataContract] public class OrdersListResponse { [DataMember(Name = "items")] public List<OrderItemDto> Items { get; set; } = new(); }
[DataContract] public class OrderItemDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "description")] public string Description { get; set; } = string.Empty; [DataMember(Name = "status")] public int Status { get; set; } [DataMember(Name = "category_name")] public string CategoryName { get; set; } = string.Empty; [DataMember(Name = "employee_fio")] public string EmployeeFullName { get; set; } = string.Empty; [DataMember(Name = "room")] public string Room { get; set; } = string.Empty; [DataMember(Name = "created_at")] public string CreatedAt { get; set; } = string.Empty; }
[DataContract] public class OrderCreateResponse { [DataMember(Name = "message")] public string Message { get; set; } = string.Empty; [DataMember(Name = "id")] public int Id { get; set; } }
[DataContract] public class OrderStatusResponse { [DataMember(Name = "message")] public string Message { get; set; } = string.Empty; }

[DataContract] public class InventoryResponse { [DataMember(Name = "items")] public List<InventoryItemDto> Items { get; set; } = new(); }
[DataContract] public class InventoryItemDto { [DataMember(Name = "employee_fio")] public string EmployeeFullName { get; set; } = string.Empty; [DataMember(Name = "name")] public string Name { get; set; } = string.Empty; [DataMember(Name = "inventory_number")] public string InventoryNumber { get; set; } = string.Empty; [DataMember(Name = "type")] public string Type { get; set; } = string.Empty; [DataMember(Name = "room")] public string Room { get; set; } = string.Empty; [DataMember(Name = "date_in")] public string DateIn { get; set; } = string.Empty; }

[DataContract] public class EmployeesResponse { [DataMember(Name = "items")] public List<EmployeeListItemDto> Items { get; set; } = new(); }
[DataContract] public class EmployeeListItemDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "login")] public string Login { get; set; } = string.Empty; [DataMember(Name = "fio")] public string FullName { get; set; } = string.Empty; [DataMember(Name = "email")] public string Email { get; set; } = string.Empty; [DataMember(Name = "active")] public bool Active { get; set; } [DataMember(Name = "role_id")] public int RoleId { get; set; } [DataMember(Name = "role_name")] public string RoleName { get; set; } = string.Empty; [DataMember(Name = "group_id")] public int GroupId { get; set; } [DataMember(Name = "group_name")] public string GroupName { get; set; } = string.Empty; }
[DataContract] public class EmployeeStatusResponse { [DataMember(Name = "message")] public string Message { get; set; } = string.Empty; }

[DataContract] public class RolesResponse { [DataMember(Name = "items")] public List<RoleListItemDto> Items { get; set; } = new(); }
[DataContract] public class RoleListItemDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "name")] public string Name { get; set; } = string.Empty; [DataMember(Name = "is_system")] public bool IsSystem { get; set; } }
[DataContract] public class RolePermissionsResponse { [DataMember(Name = "options")] public List<RolePermissionOptionDto> Options { get; set; } = new(); [DataMember(Name = "selected")] public List<string> Selected { get; set; } = new(); }
[DataContract] public class RolePermissionOptionDto { [DataMember(Name = "key")] public string Key { get; set; } = string.Empty; [DataMember(Name = "label")] public string Label { get; set; } = string.Empty; }

[DataContract] public class GroupsResponse { [DataMember(Name = "items")] public List<GroupListItemDto> Items { get; set; } = new(); }
[DataContract] public class GroupListItemDto { [DataMember(Name = "id")] public int Id { get; set; } [DataMember(Name = "name")] public string Name { get; set; } = string.Empty; [DataMember(Name = "description")] public string Description { get; set; } = string.Empty; [DataMember(Name = "students_count")] public int StudentsCount { get; set; } }

[DataContract] public class SettingsGeneralResponse { [DataMember(Name = "title")] public string Title { get; set; } = string.Empty; [DataMember(Name = "disable_reason")] public string DisableReason { get; set; } = string.Empty; [DataMember(Name = "is_enabled")] public bool IsEnabled { get; set; } }
[DataContract] public class GenericMessageResponse { [DataMember(Name = "message")] public string Message { get; set; } = string.Empty; }
