function memberAgeLabel(dob, ageTemplate) {
    const age = window.calculateAge?.(dob);
    if (age === null || age === undefined) {
        return '';
    }

    return ageTemplate.replace(':age', String(age));
}

function initGroupSetup(root) {
    if (! root || root.dataset.bound === 'true') {
        return;
    }

    let payload = { members: [], memberLabel: 'Member :n' };
    try {
        payload = JSON.parse(document.getElementById('group-setup-data')?.textContent || '{}');
    } catch {
        payload = { members: [], memberLabel: 'Member :n' };
    }

    const maxAdultDob = root.dataset.maxAdultDob || '';
    const ageTemplate = root.dataset.ageTemplate || '';
    const list = root.querySelector('[data-group-members-list]');
    const template = root.querySelector('#group-member-row-template');
    const btnAdd = root.querySelector('[data-group-add-member]');

    let members = Array.isArray(payload.members) ? payload.members.map((m) => ({ ...m })) : [];
    let openMembers = [];

    const memberLabel = (index) => payload.memberLabel.replace(':n', String(index + 1));

    const memberSummary = (index) => {
        const member = members[index];
        return [member.first_name, member.last_name].filter(Boolean).join(' ').trim();
    };

    const isMemberOpen = (index) => openMembers.includes(index);

    const syncMemberHeader = (row, index) => {
        const title = row.querySelector('[data-member-title]');
        const summary = row.querySelector('[data-member-summary]');
        const summaryText = memberSummary(index);

        if (title) {
            title.textContent = memberLabel(index);
        }

        if (summary) {
            summary.textContent = summaryText;
            summary.hidden = ! summaryText;
        }

        const removeBtn = row.querySelector('[data-member-remove]');
        if (removeBtn) {
            removeBtn.hidden = members.length <= 1;
        }
    };

    const syncAgeOnRow = (row) => {
        const dobInput = row.querySelector('[data-member-dob]');
        const ageEl = row.querySelector('[data-member-age]');
        if (! dobInput || ! ageEl) {
            return;
        }

        const label = memberAgeLabel(dobInput.value, ageTemplate);
        ageEl.textContent = label;
        ageEl.hidden = ! label;
    };

    const bindRow = (row, index) => {
        const toggle = row.querySelector('[data-member-toggle]');
        const body = row.querySelector('[data-member-body]');
        const removeBtn = row.querySelector('[data-member-remove]');
        const dobInput = row.querySelector('[data-member-dob]');

        const syncOpen = () => {
            const open = isMemberOpen(index);
            toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle?.querySelector('.collapsible-chevron')?.classList.toggle('is-open', open);
            if (body) {
                body.hidden = ! open;
            }
        };

        toggle?.addEventListener('click', () => {
            if (isMemberOpen(index)) {
                openMembers = openMembers.filter((item) => item !== index);
            } else {
                openMembers.push(index);
            }
            syncOpen();
        });

        removeBtn?.addEventListener('click', () => {
            members.splice(index, 1);
            openMembers = openMembers
                .filter((item) => item !== index)
                .map((item) => (item > index ? item - 1 : item));

            if (! openMembers.length && members.length) {
                openMembers = [Math.min(index, members.length - 1)];
            }

            renderMembers();
        });

        row.querySelectorAll('input, select').forEach((field) => {
            field.addEventListener('input', () => syncMemberHeader(row, index));
            field.addEventListener('change', () => syncMemberHeader(row, index));
        });

        dobInput?.addEventListener('change', () => syncAgeOnRow(row));
        dobInput?.addEventListener('input', () => syncAgeOnRow(row));

        syncOpen();
        syncMemberHeader(row, index);
        syncAgeOnRow(row);
    };

    const fillRow = (row, index, member) => {
        row.querySelectorAll('[data-member-field]').forEach((input) => {
            const key = input.dataset.memberField;
            const name = `members[${index}][${key}]`;
            if (input.tagName === 'SELECT' || input.tagName === 'INPUT') {
                input.name = name;
            }

            if (key in member && input.type !== 'hidden') {
                input.value = member[key] ?? '';
            }
        });

        const phoneHidden = row.querySelector('[data-phone-hidden]');
        if (phoneHidden) {
            phoneHidden.name = `members[${index}][phone]`;
        }

        const ninInput = row.querySelector('[data-nin-input]');
        if (ninInput) {
            ninInput.name = `members[${index}][nin]`;
            ninInput.value = member.nin || '';
        }

        const dobInput = row.querySelector('[data-member-dob]');
        if (dobInput) {
            dobInput.max = maxAdultDob;
        }
    };

    const renderMembers = () => {
        if (! list || ! template) {
            return;
        }

        list.innerHTML = '';
        members.forEach((member, index) => {
            const row = template.content.firstElementChild.cloneNode(true);
            fillRow(row, index, member);
            list.appendChild(row);
            bindRow(row, index);
        });

        window.initIdentityInputs?.(root);
    };

    const addMember = () => {
        members.push({
            first_name: '',
            middle_name: '',
            last_name: '',
            nin: '',
            dob: '',
            phone: '',
            email: '',
            sex: '',
            marital_status: '',
            leadership_role: '',
        });

        openMembers = [members.length - 1];
        renderMembers();
    };

    btnAdd?.addEventListener('click', addMember);

    if (! members.length) {
        addMember();
    } else {
        openMembers = [0];
        renderMembers();
    }

    root.dataset.bound = 'true';
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-group-setup]').forEach(initGroupSetup);
});
