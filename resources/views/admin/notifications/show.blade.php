@extends('admin.layout')

@section('title', 'Notification Details')
@php($active = 'notifications')

@section('content')
<div class="page-header">
    <div>
        <h2>Notification Details</h2>
        <p>Review delivery metadata and resend if needed.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/notifications'">Back to list</button>
    </div>
</div>

<div class="card">
    <div id="notification-detail" class="status">Loading...</div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn secondary" id="btn-resend">Resend</button>
        <button class="btn secondary" id="btn-mark">Mark as sent</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    const notificationId = {{ $id }};

    async function loadNotification() {
        const res = await AdminApp.api(`/api/admin/notifications/${notificationId}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('notification-detail').textContent = data.message || 'Failed to load notification.';
            return;
        }
        const n = data.notification;
        document.getElementById('notification-detail').innerHTML = `
            <div><strong>${n.type || '—'}</strong></div>
            <div class="status">Channel: ${n.channel || '—'}</div>
            <div class="status">User ID: ${n.user_id || '—'}</div>
            <div class="status">Company ID: ${n.company_id || '—'}</div>
            <div class="status">Document ID: ${n.document_id || '—'}</div>
            <div class="status">Sent at: ${n.sent_at || 'Pending'}</div>
        `;
    }

    document.getElementById('btn-resend').addEventListener('click', async () => {
        await AdminApp.api(`/api/admin/notifications/${notificationId}/resend`, { method: 'POST' });
        loadNotification();
    });
    document.getElementById('btn-mark').addEventListener('click', async () => {
        await AdminApp.api(`/api/admin/notifications/${notificationId}/mark-sent`, { method: 'POST' });
        loadNotification();
    });

    loadNotification();
</script>
@endsection
