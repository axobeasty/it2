using System;
using System.IO;

namespace ItMaster.Desktop.Wpf.Infrastructure.Storage;

public class TokenStore
{
    private readonly string _tokenPath;

    public TokenStore()
    {
        var appDir = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "ItMasterDesktopWpf");

        if (!Directory.Exists(appDir))
        {
            Directory.CreateDirectory(appDir);
        }

        _tokenPath = Path.Combine(appDir, "session.token");
    }

    public void Save(string token)
    {
        File.WriteAllText(_tokenPath, token ?? string.Empty);
    }

    public string Load()
    {
        if (!File.Exists(_tokenPath))
        {
            return string.Empty;
        }

        return File.ReadAllText(_tokenPath).Trim();
    }

    public void Clear()
    {
        if (File.Exists(_tokenPath))
        {
            File.Delete(_tokenPath);
        }
    }
}
