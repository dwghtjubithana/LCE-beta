<div class="card" style="padding:12px;">
    <div class="settings-nav">
        <a href="/admin/settings" class="{{ ($active ?? '') === 'settings' ? 'active' : '' }}">Overview</a>
        <a href="/admin/system" class="{{ ($active ?? '') === 'system' ? 'active' : '' }}">System</a>
        <a href="/admin/ai-settings" class="{{ ($active ?? '') === 'ai-settings' ? 'active' : '' }}">AI</a>
        <a href="/admin/email-settings" class="{{ ($active ?? '') === 'email-settings' ? 'active' : '' }}">Email</a>
        <a href="/admin/auth-providers" class="{{ ($active ?? '') === 'auth-providers' ? 'active' : '' }}">Auth Providers</a>
    </div>
</div>
