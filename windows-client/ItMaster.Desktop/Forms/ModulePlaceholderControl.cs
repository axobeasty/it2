using System.Drawing;
using System.Windows.Forms;

namespace ItMaster.Desktop.Forms
{
    public class ModulePlaceholderControl : UserControl
    {
        public ModulePlaceholderControl(string title, string description)
        {
            Dock = DockStyle.Fill;
            BackColor = Color.White;

            var titleLabel = new Label
            {
                Text = title,
                Dock = DockStyle.Top,
                Height = 36,
                Font = new Font("Segoe UI", 14, FontStyle.Bold),
                Padding = new Padding(12, 8, 12, 0),
            };

            var descriptionLabel = new Label
            {
                Text = description,
                Dock = DockStyle.Top,
                Height = 64,
                Font = new Font("Segoe UI", 10),
                Padding = new Padding(12, 6, 12, 0),
            };

            Controls.Add(descriptionLabel);
            Controls.Add(titleLabel);
        }
    }
}
