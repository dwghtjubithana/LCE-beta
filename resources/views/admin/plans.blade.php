@extends('admin.layout')

@section('title', 'Plan Catalog')
@php($active = 'plans')

@section('content')
<div class="page-header">
    <div>
        <h2>Plan Catalog</h2>
        <p>Beheer plan keys, labels en upgrade-gedrag centraal.</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Nieuw plan</h3>
    <div class="form-stack">
        <div class="form-field">
            <label for="new-plan-key">Plan key</label>
            <input class="input" id="new-plan-key" placeholder="Bijv. SUPPLIER_PLUS">
        </div>
        <div class="form-field">
            <label for="new-plan-label">Label</label>
            <input class="input" id="new-plan-label" placeholder="Bijv. Supplier Plus">
        </div>
        <div class="form-field">
            <label for="new-plan-rank">Rank</label>
            <input class="input" id="new-plan-rank" type="number" min="0" max="9999" value="50">
        </div>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-create-plan">Plan toevoegen</button>
    </div>
    <div class="status" id="create-plan-status"></div>
</div>

<div class="card">
    <div id="plans-table"></div>
    <div class="status" id="plans-status"></div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    let plans = [];

    function boolSelect(value) {
        return `
            <select class="input js-bool" data-value="${value ? '1' : '0'}">
                <option value="1" ${value ? 'selected' : ''}>Yes</option>
                <option value="0" ${value ? '' : 'selected'}>No</option>
            </select>
        `;
    }

    function escapeHtml(value) {
        const str = String(value ?? '');
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderPlans() {
        const wrap = document.getElementById('plans-table');
        if (!plans.length) {
            wrap.innerHTML = '<div class="status">Geen plannen gevonden.</div>';
            return;
        }

        wrap.innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Label</th>
                        <th>Rank</th>
                        <th>Active</th>
                        <th>Default</th>
                        <th>Signup</th>
                        <th>Upgrade</th>
                        <th>Proof</th>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>
                    ${plans.map((plan) => `
                        <tr data-id="${plan.id}">
                            <td><input class="input js-key" value="${escapeHtml(plan.plan_key || '')}" style="min-width:140px;"></td>
                            <td><input class="input js-label" value="${escapeHtml(plan.plan_label || '')}" style="min-width:140px;"></td>
                            <td><input class="input js-rank" type="number" min="0" max="9999" value="${Number(plan.rank || 0)}" style="max-width:90px;"></td>
                            <td>${boolSelect(!!plan.is_active)}</td>
                            <td>${boolSelect(!!plan.is_default)}</td>
                            <td>${boolSelect(!!plan.available_for_signup)}</td>
                            <td>${boolSelect(!!plan.available_for_upgrade)}</td>
                            <td>${boolSelect(!!plan.requires_payment_proof)}</td>
                            <td><button class="btn secondary js-save">Opslaan</button></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    async function loadPlans() {
        const statusEl = document.getElementById('plans-status');
        const res = await AdminApp.api('/api/admin/plans?include_inactive=1');
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        plans = Array.isArray(data.plans) ? data.plans : [];
        renderPlans();
        AdminApp.setStatus(statusEl, '', '');
    }

    async function createPlan() {
        const statusEl = document.getElementById('create-plan-status');
        const payload = {
            plan_key: document.getElementById('new-plan-key').value.trim().toUpperCase(),
            plan_label: document.getElementById('new-plan-label').value.trim(),
            rank: Number(document.getElementById('new-plan-rank').value) || 0,
            is_active: true,
            is_default: false,
            available_for_signup: true,
            available_for_upgrade: true,
            requires_payment_proof: false,
        };
        if (!payload.plan_key || !payload.plan_label) {
            AdminApp.setStatus(statusEl, 'Plan key en label zijn verplicht.', 'error');
            return;
        }
        const res = await AdminApp.api('/api/admin/plans', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'Plan toegevoegd.', 'success');
        document.getElementById('new-plan-key').value = '';
        document.getElementById('new-plan-label').value = '';
        await loadPlans();
    }

    async function savePlan(row) {
        const id = row.getAttribute('data-id');
        if (!id) return;
        const bools = Array.from(row.querySelectorAll('.js-bool')).map((el) => el.value === '1');
        const payload = {
            plan_key: row.querySelector('.js-key')?.value?.trim()?.toUpperCase() || null,
            plan_label: row.querySelector('.js-label')?.value?.trim() || null,
            rank: Number(row.querySelector('.js-rank')?.value || 0),
            is_active: bools[0] ?? true,
            is_default: bools[1] ?? false,
            available_for_signup: bools[2] ?? true,
            available_for_upgrade: bools[3] ?? true,
            requires_payment_proof: bools[4] ?? false,
        };
        const res = await AdminApp.api(`/api/admin/plans/${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await AdminApp.readJson(res);
        const statusEl = document.getElementById('plans-status');
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'Plan opgeslagen.', 'success');
        await loadPlans();
    }

    document.getElementById('btn-create-plan').addEventListener('click', createPlan);
    document.getElementById('plans-table').addEventListener('click', (e) => {
        const btn = e.target.closest('.js-save');
        if (!btn) return;
        const row = btn.closest('tr[data-id]');
        if (row) savePlan(row);
    });

    loadPlans();
</script>
@endsection
