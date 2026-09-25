function setText(root, selector, value) {
    const el = root.querySelector(selector);
    if (el) {
        el.textContent = value ?? '';
    }
}

function initGroupShow(root) {
    if (! root || root.dataset.bound === 'true') {
        return;
    }

    let members = [];
    try {
        members = JSON.parse(document.getElementById('group-members-data')?.textContent || '[]');
    } catch {
        members = [];
    }

    const ageTemplate = root.dataset.ageTemplate || '';
    const viewModal = document.getElementById('app-modal-view-member');
    const editModal = document.getElementById('app-modal-edit-member');
    const removeModal = document.getElementById('confirm-modal-remove-member');

    const editLeaderForm = root.querySelector('[data-edit-member-leader]');
    const editRegularForm = root.querySelector('[data-edit-member-regular]');
    const removeForm = document.querySelector('[data-remove-member-form]');
    const removeNameEl = document.querySelector('[data-remove-member-name]');

    const ageLabel = (dob) => {
        const age = window.calculateAge?.(dob);
        if (age === null || age === undefined) {
            return '';
        }

        return ageTemplate.replace(':age', String(age));
    };

    const syncEditAge = (form) => {
        const dobInput = form?.querySelector('[data-edit-dob]');
        const ageEl = form?.querySelector('[data-edit-age]');
        if (! dobInput || ! ageEl) {
            return;
        }

        const label = ageLabel(dobInput.value);
        ageEl.textContent = label;
        ageEl.hidden = ! label;
    };

    const openMember = (index) => members[index] ?? null;

    const populateView = (member) => {
        if (! viewModal) {
            return;
        }

        setText(viewModal, '[data-view-field="full_name"]', member.full_name);
        setText(viewModal, '[data-view-field="first_name"]', member.first_name);
        setText(viewModal, '[data-view-field="middle_name"]', member.middle_name || '—');
        setText(viewModal, '[data-view-field="last_name"]', member.last_name);
        setText(viewModal, '[data-view-field="leadership_role_label"]', member.leadership_role_label);
        setText(viewModal, '[data-view-field="nin"]', member.nin);
        setText(viewModal, '[data-view-field="dob_label"]', member.dob_label ?? '—');
        setText(viewModal, '[data-view-field="sex"]', member.sex ?? '—');
        setText(viewModal, '[data-view-field="marital_status_label"]', member.marital_status_label ?? '—');
        setText(viewModal, '[data-view-field="phone"]', member.phone);
        setText(viewModal, '[data-view-field="email"]', member.email || '—');
    };

    const populateEdit = (member) => {
        if (editLeaderForm) {
            editLeaderForm.hidden = ! member.is_group_leader;
        }
        if (editRegularForm) {
            editRegularForm.hidden = Boolean(member.is_group_leader);
        }

        if (member.is_group_leader && editLeaderForm) {
            editLeaderForm.action = member.update_url || '';
            const fn = editLeaderForm.querySelector('[data-edit-readonly="first_name"]');
            const mn = editLeaderForm.querySelector('[data-edit-readonly="middle_name"]');
            const ln = editLeaderForm.querySelector('[data-edit-readonly="last_name"]');
            if (fn) {
                fn.value = member.first_name || '';
            }
            if (mn) {
                mn.value = member.middle_name || '';
            }
            if (ln) {
                ln.value = member.last_name || '';
            }
            const dob = editLeaderForm.querySelector('[data-edit-dob]');
            if (dob) {
                dob.value = member.dob || '';
            }
            const leadership = editLeaderForm.querySelector('[name="leadership_role"]');
            if (leadership) {
                leadership.value = member.leadership_role || '';
                window.AppSelect?.refreshAppSelect(leadership);
            }
            syncEditAge(editLeaderForm);
        }

        if (! member.is_group_leader && editRegularForm) {
            editRegularForm.action = member.update_url || '';
            const setVal = (name, value) => {
                const field = editRegularForm.querySelector(`[name="${name}"]`);
                if (field) {
                    field.value = value || '';
                }
            };
            setVal('first_name', member.first_name);
            setVal('middle_name', member.middle_name);
            setVal('last_name', member.last_name);
            setVal('nin', member.nin);
            const dob = editRegularForm.querySelector('[data-edit-dob]');
            if (dob) {
                dob.value = member.dob || '';
            }
            setVal('phone', member.phone);
            setVal('email', member.email);
            const marital = editRegularForm.querySelector('[name="marital_status"]');
            if (marital) {
                marital.value = member.marital_status || '';
                window.AppSelect?.refreshAppSelect(marital);
            }
            const leadership = editRegularForm.querySelector('[name="leadership_role"]');
            if (leadership) {
                leadership.value = member.leadership_role || '';
                window.AppSelect?.refreshAppSelect(leadership);
            }
            syncEditAge(editRegularForm);
        }
    };

    [editLeaderForm, editRegularForm].forEach((form) => {
        form?.querySelector('[data-edit-dob]')?.addEventListener('input', () => syncEditAge(form));
        form?.querySelector('[data-edit-dob]')?.addEventListener('change', () => syncEditAge(form));
    });

    root.querySelectorAll('[data-member-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const index = Number(button.dataset.memberIndex);
            const action = button.dataset.memberAction;
            const member = openMember(index);
            if (! member) {
                return;
            }

            if (action === 'view' && viewModal) {
                populateView(member);
                window.AppModal?.open(viewModal);
            } else if (action === 'edit' && editModal) {
                populateEdit(member);
                window.AppModal?.open(editModal);
            } else if (action === 'remove' && removeModal) {
                if (removeForm) {
                    removeForm.action = member.destroy_url || '';
                }
                if (removeNameEl) {
                    removeNameEl.textContent = member.full_name || '';
                }
                window.AppModal?.open(removeModal);
            }
        });
    });

    root.dataset.bound = 'true';
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-group-show]').forEach(initGroupShow);
});
