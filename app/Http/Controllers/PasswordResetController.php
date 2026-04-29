<?php

namespace App\Http\Controllers;

use App\Http\Email\Email;
use App\Models\Employee;
use App\Models\EmployeePasswordReset;
use App\Models\MailDeliveryFailure;
use App\Models\Settings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function showForgotForm(Request $request)
    {
        if ($request->session()->has('user')) {
            return redirect('/dashboard');
        }

        $settings = Settings::query()->find(1);

        return view('password.forgot', compact('settings'));
    }

    public function sendResetLink(Request $request)
    {
        if ($request->session()->has('user')) {
            return redirect('/dashboard');
        }

        $settings = Settings::query()->find(1);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $emailNorm = mb_strtolower(trim($validated['email']));

        if (! $settings || (int) $settings->email_enabled !== 1 || trim((string) ($settings->smtp_host ?? '')) === '') {
            Toastr::error(
                'Отправка писем отключена или не настроена SMTP. Обратитесь к администратору.',
                'Восстановление пароля',
                ['progressBar' => true]
            );

            return redirect()->route('password.forgot')->withInput();
        }

        $employee = Employee::query()
            ->whereRaw('lower(email) = ?', [$emailNorm])
            ->where('active', 1)
            ->first();

        $genericMessage = 'Если указанный адрес привязан к активному аккаунту, на него отправлена ссылка для сброса пароля.';

        if ($employee === null || trim((string) $employee->email) === '') {
            Toastr::success($genericMessage, 'Восстановление пароля', ['progressBar' => true]);

            return redirect()->route('password.forgot');
        }

        EmployeePasswordReset::query()->where('employee_id', $employee->id)->delete();

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        EmployeePasswordReset::query()->create([
            'employee_id' => $employee->id,
            'token' => $tokenHash,
            'expires_at' => now()->addHour(),
        ]);

        $resetUrl = url('/password/reset/'.$plainToken);
        $title = 'Восстановление пароля — '.$settings->title;
        $body = '<p>Здравствуйте, '.e($employee->fio).'.</p>'
            .'<p>Для установки нового пароля перейдите по ссылке (действует 1 час):</p>'
            .'<p><a href="'.e($resetUrl).'">'.e($resetUrl).'</a></p>'
            .'<p>Если вы не запрашивали сброс пароля, проигнорируйте это письмо.</p>';

        $mail = new Email();
        $sent = $mail->send($title, $body, $employee->email, $settings->title, [
            'category' => MailDeliveryFailure::CATEGORY_AUTH,
            'mail_type' => 'password_reset',
            'recipient_employee_id' => $employee->id,
            'recipient_name' => $employee->fio,
            'triggered_by_employee_id' => $employee->id,
            'meta' => ['source' => 'password_forgot'],
        ]);

        if (! $sent) {
            Toastr::warning(
                'Не удалось отправить письмо. Проверьте настройки почты или журнал ошибок доставки.',
                'Восстановление пароля',
                ['progressBar' => true]
            );

            return redirect()->route('password.forgot')->withInput();
        }

        Toastr::success($genericMessage, 'Восстановление пароля', ['progressBar' => true]);

        return redirect()->route('password.forgot');
    }

    public function showResetForm(Request $request, string $token)
    {
        if ($request->session()->has('user')) {
            return redirect('/dashboard');
        }

        $settings = Settings::query()->find(1);
        $token = trim($token);
        if ($token === '' || strlen($token) !== 64 || ! ctype_xdigit($token)) {
            Toastr::error('Ссылка восстановления недействительна или устарела.', 'Ошибка', ['progressBar' => true]);

            return redirect('/');
        }

        $hash = hash('sha256', $token);
        $row = EmployeePasswordReset::query()
            ->where('token', $hash)
            ->where('expires_at', '>', now())
            ->first();

        if ($row === null) {
            Toastr::error('Ссылка восстановления недействительна или устарела.', 'Ошибка', ['progressBar' => true]);

            return redirect('/');
        }

        return view('password.reset', compact('settings', 'token'));
    }

    public function reset(Request $request)
    {
        if ($request->session()->has('user')) {
            return redirect('/dashboard');
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $plain = $validated['token'];
        if (! ctype_xdigit($plain)) {
            Toastr::error('Неверный токен восстановления.', 'Ошибка', ['progressBar' => true]);

            return redirect('/');
        }

        $hash = hash('sha256', $plain);
        $row = EmployeePasswordReset::query()
            ->where('token', $hash)
            ->where('expires_at', '>', now())
            ->first();

        if ($row === null) {
            Toastr::error('Ссылка восстановления недействительна или устарела.', 'Ошибка', ['progressBar' => true]);

            return redirect('/');
        }

        $employee = Employee::query()->where('id', $row->employee_id)->where('active', 1)->first();
        if ($employee === null) {
            $row->delete();
            Toastr::error('Аккаунт недоступен. Обратитесь к администратору.', 'Ошибка', ['progressBar' => true]);

            return redirect('/');
        }

        $employee->password = Hash::make($validated['password']);
        $employee->save();

        EmployeePasswordReset::query()->where('employee_id', $employee->id)->delete();

        Toastr::success('Пароль успешно изменён. Войдите с новым паролём.', 'Готово', ['progressBar' => true]);

        return redirect('/');
    }
}
