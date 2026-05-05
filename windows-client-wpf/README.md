# IT-Master Desktop WPF (.NET 8)

Полный rewrite WinForms-клиента на WPF с API-first интеграцией (`/api/mobile/...`).

## Что реализовано
- WPF shell: `LoginWindow` + `MainWindow`.
- Локальная настройка API URL (переключение сервера без правки конфигов).
- Восстановление сессии по токену.
- Модульные вкладки:
  - Расписание
  - Тесты
  - Статистика тестов
  - Уведомления
  - Заявки
  - Инвентарь
  - Wiki
  - Пользователи и роли
  - Настройки
- Legacy fallback по правам (если API возвращает неполный payload `permissions`).

## Сборка
```bash
dotnet build ItMaster.WindowsClient.Wpf.sln -c Release
```

## Выходной exe
`ItMaster.Desktop.Wpf/bin/Release/net8.0-windows/ItMaster.Desktop.Wpf.exe`
