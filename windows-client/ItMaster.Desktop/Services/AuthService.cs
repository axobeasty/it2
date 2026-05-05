using System;
using System.Threading.Tasks;
using ItMaster.Desktop.Infrastructure.Api;
using ItMaster.Desktop.Infrastructure.Storage;
using ItMaster.Desktop.Models;

namespace ItMaster.Desktop.Services
{
    public class AuthService
    {
        private readonly ApiClient _apiClient;
        private readonly TokenStore _tokenStore;

        public AuthService(ApiClient apiClient, TokenStore tokenStore)
        {
            _apiClient = apiClient;
            _tokenStore = tokenStore;
        }

        public async Task<UserDto> LoginAsync(string login, string password)
        {
            var response = await _apiClient.LoginAsync(login, password);
            if (string.IsNullOrWhiteSpace(response.Token))
            {
                throw new InvalidOperationException("Server did not return a token.");
            }

            _tokenStore.Save(response.Token);
            return response.User;
        }

        public async Task<UserDto> TryRestoreSessionAsync()
        {
            var token = _tokenStore.Load();
            if (string.IsNullOrWhiteSpace(token))
            {
                return null;
            }

            try
            {
                return await _apiClient.MeAsync(token);
            }
            catch
            {
                _tokenStore.Clear();
                return null;
            }
        }

        public void Logout()
        {
            _tokenStore.Clear();
        }

        public string GetActiveToken()
        {
            return _tokenStore.Load();
        }
    }
}
