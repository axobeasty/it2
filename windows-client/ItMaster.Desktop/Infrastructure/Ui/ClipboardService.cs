using System;
using System.Windows.Forms;

namespace ItMaster.Desktop.Infrastructure.Ui
{
    internal static class ClipboardService
    {
        public static bool TrySetText(string text, out string error)
        {
            error = string.Empty;
            try
            {
                if (string.IsNullOrWhiteSpace(text))
                {
                    error = "Нет данных для копирования.";
                    return false;
                }

                Clipboard.SetText(text);
                return true;
            }
            catch (Exception ex)
            {
                error = "Не удалось скопировать в буфер обмена: " + ex.Message;
                return false;
            }
        }
    }
}
