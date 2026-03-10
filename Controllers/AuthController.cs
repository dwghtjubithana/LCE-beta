using Microsoft.AspNetCore.Mvc;
using Microsoft.IdentityModel.Tokens;
using MySql.Data.MySqlClient;
using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Text;
using System.Data;
using FirebaseAdmin.Auth; // Ensure you have installed the FirebaseAdmin NuGet package

namespace Wapcore.Controllers
{
    [ApiController]
    [Route("api/[controller]")]
    public class AuthController : ControllerBase
    {
        private readonly IConfiguration _configuration;
        private readonly string _jwtSecret;
        private readonly string _connectionString;

        public AuthController(IConfiguration configuration)
        {
            _configuration = configuration;
            _jwtSecret = _configuration["Jwt:Secret"] ?? "Wapcomtek_Monster_Secret_Key_2025_Secure";
            _connectionString = _configuration.GetConnectionString("DefaultConnection");
        }

        // --- NEW: CLOUD AUTH HANDLER (For Google/Microsoft Bridge) ---
        [HttpPost("verify-cloud")]
        public async Task<IActionResult> VerifyCloud([FromBody] CloudAuthRequest request)
        {
            try
            {
                // 1. Verify the Firebase Token sent from the frontend
                FirebaseToken decodedToken = await FirebaseAuth.DefaultInstance.VerifyIdTokenAsync(request.FirebaseToken);
                string uid = decodedToken.Uid;
                string email = decodedToken.Email;

                using (var connection = new MySqlConnection(_connectionString))
                {
                    await connection.OpenAsync();
                    
                    // 2. Look for the user in MySQL using the firebase_uid
                    string sql = "SELECT id, role, status FROM users WHERE firebase_uid = @uid";
                    using (var command = new MySqlCommand(sql, connection))
                    {
                        command.Parameters.AddWithValue("@uid", uid);
                        using (var reader = await command.ExecuteReaderAsync())
                        {
                            if (await reader.ReadAsync())
                            {
                                if (reader.GetString("status").ToLower() != "active")
                                    return Unauthorized(new { message = "Account Suspended" });

                                string role = reader.GetString("role");
                                string dbId = reader.GetInt32("id").ToString();

                                // 3. Log the successful Bridge access
                                await UpdateLastLogin(dbId, connection);

                                // 4. Generate Wapcore JWT for the session
                                var token = GenerateJwtToken(dbId, role);

                                return Ok(new
                                {
                                    token = token,
                                    userId = "WAP-" + dbId.PadLeft(4, '0'),
                                    role = role,
                                    user = email,
                                    message = "Enterprise Link Verified"
                                });
                            }
                        }
                    }
                }
                return Unauthorized(new { message = "User not registered in Wapcore Database." });
            }
            catch (Exception ex)
            {
                return BadRequest(new { message = "Security Handshake Failed", details = ex.Message });
            }
        }

        // --- EXISTING: LEGACY LOGIN (Updated to check for 'ACTIVE' enum) ---
        [HttpPost("login")]
        public async Task<IActionResult> Login([FromBody] LoginRequest request)
        {
            using (var connection = new MySqlConnection(_connectionString))
            {
                await connection.OpenAsync();

                string sql = @"SELECT id, username, role FROM users 
                               WHERE username = @u AND password_hash = @p AND status = 'ACTIVE'";

                using (var command = new MySqlCommand(sql, connection))
                {
                    command.Parameters.AddWithValue("@u", request.Username);
                    command.Parameters.AddWithValue("@p", request.Password);

                    using (var reader = await command.ExecuteReaderAsync())
                    {
                        if (await reader.ReadAsync())
                        {
                            var userId = reader.GetInt32("id").ToString();
                            var role = reader.GetString("role");

                            var token = GenerateJwtToken(userId, role);

                            return Ok(new
                            {
                                token = token,
                                userId = "WAP-" + userId.PadLeft(4, '0'),
                                role = role,
                                message = "Access Granted"
                            });
                        }
                    }
                }
            }
            return Unauthorized(new { message = "Invalid credentials or account deactivated." });
        }

        private async Task UpdateLastLogin(string userId, MySqlConnection connection)
        {
            // Note: Use a separate connection or ensure the reader is closed before this
            string sql = "UPDATE users SET last_login = @now WHERE id = @id";
            using (var cmd = new MySqlCommand(sql, connection))
            {
                cmd.Parameters.AddWithValue("@now", DateTime.UtcNow);
                cmd.Parameters.AddWithValue("@id", userId);
                await cmd.ExecuteNonQueryAsync();
            }
        }

        private string GenerateJwtToken(string userId, string role)
        {
            var tokenHandler = new JwtSecurityTokenHandler();
            var key = Encoding.ASCII.GetBytes(_jwtSecret);
            var tokenDescriptor = new SecurityTokenDescriptor
            {
                Subject = new ClaimsIdentity(new[] 
                { 
                    new Claim(ClaimTypes.NameIdentifier, userId),
                    new Claim(ClaimTypes.Role, role)
                }),
                Expires = DateTime.UtcNow.AddHours(8),
                SigningCredentials = new SigningCredentials(new SymmetricSecurityKey(key), SecurityAlgorithms.HmacSha256Signature)
            };
            var token = tokenHandler.CreateToken(tokenDescriptor);
            return tokenHandler.WriteToken(token);
        }
    }

    public class LoginRequest { public string Username { get; set; } public string Password { get; set; } }
    public class CloudAuthRequest { public string FirebaseToken { get; set; } }
}