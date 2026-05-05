using System;
using System.IO;

namespace ItMaster.Desktop.Wpf.Infrastructure.Storage;

public class ApiSettingsStore
{
    private readonly string _path;

    public ApiSettingsStore()
    {
        var appDir = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "ItMasterDesktopWpf");

        if (!Directory.Exists(appDir))
        {
            Directory.CreateDirectory(appDir);
        }

        _path = Path.Combine(appDir, "api.url");
    }

    public string Load(string fallback)
    {
        if (!File.Exists(_path))
        {
            return Normalize(fallback);
        }

        var raw = File.ReadAllText(_path).Trim();
        return string.IsNullOrWhiteSpace(raw) ? Normalize(fallback) : Normalize(raw);
    }

    public void Save(string baseUrl)
    {
        File.WriteAllText(_path, Normalize(baseUrl));
    }

    private static string Normalize(string value)
    {
        var url = (value ?? string.Empty).Trim();
        if (url == string.Empty)
        {
            return string.Empty;
        }

        return url.EndsWith("/", StringComparison.Ordinal) ? url : url + "/";
    }
}
