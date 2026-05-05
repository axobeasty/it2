namespace ItMaster.Desktop.Wpf.ViewModels;

public class LoginViewModel : BaseViewModel
{
    private string _apiUrl = string.Empty;
    private string _login = string.Empty;
    private string _status = string.Empty;

    public string ApiUrl { get => _apiUrl; set { _apiUrl = value; OnPropertyChanged(); } }
    public string Login { get => _login; set { _login = value; OnPropertyChanged(); } }
    public string Status { get => _status; set { _status = value; OnPropertyChanged(); } }
}
