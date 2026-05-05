namespace ItMaster.Desktop.Wpf.ViewModels;

public class MainViewModel : BaseViewModel
{
    private string _status = string.Empty;
    public string Status { get => _status; set { _status = value; OnPropertyChanged(); } }
}
