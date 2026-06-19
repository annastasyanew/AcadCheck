const storage = {
    getToken: () => localStorage.getItem('acadcheck_token'),
    getUser: () => {
        try {
            return JSON.parse(localStorage.getItem('acadcheck_user') || 'null');
        } catch {
            return null;
        }
    },
    saveSession: (token, user) => {
        localStorage.setItem('acadcheck_token', token);
        localStorage.setItem('acadcheck_user', JSON.stringify(user));
    },
    clearSession: () => {
        localStorage.removeItem('acadcheck_token');
        localStorage.removeItem('acadcheck_user');
    },
};

const redirectForUser = (user) => {
    window.location.href = user?.role === 'admin' ? '/admin/dashboard' : '/dashboard';
};

const getErrorMessage = (data, fallback) => {
    const validationMessage = Object.values(data?.errors || {})[0]?.[0];
    return validationMessage || data?.message || fallback;
};

const showAlert = (message, id = 'authAlert', type = 'error') => {
    const alert = document.getElementById(id);

    if (!alert) return;

    alert.textContent = message;
    alert.dataset.type = type;
    alert.classList.remove('hidden');
};

const hideAlert = (id = 'authAlert') => document.getElementById(id)?.classList.add('hidden');

const setSubmitting = (form, isSubmitting) => {
    const button = form.querySelector('button[type="submit"]');

    button.disabled = isSubmitting;
    button.querySelector('.button-label')?.classList.toggle('hidden', isSubmitting);
    button.querySelector('.button-loader')?.classList.toggle('hidden', !isSubmitting);
};

const submitAuthForm = async (form, endpoint, fallbackMessage) => {
    hideAlert();

    if (!form.reportValidity()) return;

    setSubmitting(form, true);

    try {
        const payload = Object.fromEntries(new FormData(form).entries());
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();

        if (!response.ok) {
            showAlert(getErrorMessage(data, fallbackMessage));
            return;
        }

        storage.saveSession(data.token, data.user);
        redirectForUser(data.user);
    } catch {
        showAlert('Tidak dapat terhubung ke server. Silakan coba kembali.');
    } finally {
        setSubmitting(form, false);
    }
};

const initializeAuthPage = (page) => {
    const currentUser = storage.getUser();

    if (storage.getToken() && currentUser) {
        redirectForUser(currentUser);
        return;
    }

    if (page === 'login') {
        document.getElementById('loginForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            submitAuthForm(event.currentTarget, '/api/login', 'Login gagal.');
        });
    }

    if (page === 'register') {
        document.getElementById('registerForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            submitAuthForm(event.currentTarget, '/api/register', 'Registrasi gagal.');
        });
    }
};

const logout = async () => {
    const token = storage.getToken();

    if (token) {
        try {
            await fetch('/api/logout', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token}`,
                },
            });
        } catch {
            // Session lokal tetap dibersihkan jika server tidak dapat dijangkau.
        }
    }

    storage.clearSession();
    window.location.href = '/login';
};

const requireUserSession = (allowAdmin = false) => {
    const token = storage.getToken();
    const user = storage.getUser();

    if (!token || !user) {
        window.location.href = '/login';
        return null;
    }

    if (!allowAdmin && user.role === 'admin') {
        window.location.href = '/admin/dashboard';
        return null;
    }

    return { token, user };
};

const initializeDashboardPlaceholder = (page) => {
    const session = requireUserSession(page === 'admin-dashboard');

    if (!session) return;

    const { user } = session;

    if (page === 'admin-dashboard' && user.role !== 'admin') {
        window.location.href = '/dashboard';
        return;
    }

    const userBox = document.getElementById('currentUser');

    if (userBox) {
        userBox.textContent = `${user.name} | ${user.email}`;
    }

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
};

const createUserDashboardActivityRow = (documentData) => {
    const row = document.createElement('tr');
    const documentCell = document.createElement('td');
    const statusCell = document.createElement('td');
    const actionCell = document.createElement('td');
    const detailLink = createTextElement('a', 'detail-link', 'Detail');

    documentCell.append(
        createTextElement('strong', 'document-title', documentData.title || '-'),
        createTextElement('span', 'document-topic', documentData.topic || 'Tanpa topik'),
    );
    statusCell.appendChild(createTextElement(
        'span',
        `status-badge status-${documentData.status}`,
        documentStatusLabels[documentData.status] || documentData.status || '-',
    ));
    detailLink.href = `/documents/${documentData.id}`;
    actionCell.appendChild(detailLink);
    row.append(
        documentCell,
        createTextElement('td', '', documentData.document_type?.label || '-'),
        statusCell,
        createTextElement('td', 'score-cell', documentData.latest_score ?? '-'),
        actionCell,
    );

    return row;
};

const renderUserDashboardActivities = (documents) => {
    const body = document.getElementById('latestActivities');

    if (!documents.length) {
        const row = document.createElement('tr');
        const cell = createTextElement('td', 'comparison-no-data', 'Belum ada aktivitas dokumen.');
        cell.colSpan = 5;
        row.appendChild(cell);
        body.replaceChildren(row);
        return;
    }

    body.replaceChildren(...documents.map(createUserDashboardActivityRow));
};

const createUserDashboardPriorityItem = (documentData) => {
    const item = document.createElement('article');
    const content = document.createElement('div');
    const meta = document.createElement('p');
    const link = createTextElement('a', 'detail-link', 'Lanjutkan revisi');

    item.className = 'dashboard-priority-item';
    content.appendChild(createTextElement('strong', '', documentData.title || 'Dokumen tanpa judul'));
    meta.textContent = [
        documentData.document_type?.label || 'Dokumen',
        documentStatusLabels[documentData.status] || documentData.status || '-',
        `Skor ${documentData.latest_score ?? '-'}`,
    ].join(' | ');
    content.appendChild(meta);
    link.href = `/documents/${documentData.id}`;
    item.append(content, link);

    return item;
};

const renderUserDashboardPriorities = (documents) => {
    const container = document.getElementById('revisionPriorities');

    if (!container) return;

    if (!documents.length) {
        const emptyState = document.createElement('div');
        emptyState.className = 'dashboard-priority-empty';
        emptyState.append(
            createTextElement('strong', '', 'Belum ada dokumen prioritas revisi.'),
            createTextElement('p', '', 'Dokumen yang membutuhkan revisi akan muncul di sini.'),
        );
        container.replaceChildren(emptyState);
        return;
    }

    container.replaceChildren(...documents.map(createUserDashboardPriorityItem));
};

const renderUserDashboard = (dashboard) => {
    const summary = dashboard.summary || {};
    const needRevision = summary.by_status?.need_revision ?? 0;
    const readyDocuments = summary.by_status?.ready ?? 0;
    const averageScore = summary.average_score ?? 0;
    const statusNarrative = document.getElementById('dashboardStatusNarrative');

    document.getElementById('totalDocuments').textContent = summary.total_documents ?? 0;
    document.getElementById('averageScore').textContent = averageScore;
    document.getElementById('needRevision').textContent = needRevision;
    document.getElementById('readyDocuments').textContent = readyDocuments;
    document.getElementById('totalArticle').textContent = summary.by_type?.article ?? 0;
    document.getElementById('totalProposal').textContent = summary.by_type?.proposal ?? 0;
    document.getElementById('totalReport').textContent = summary.by_type?.report ?? 0;
    if (statusNarrative) {
        statusNarrative.textContent = needRevision > 0
            ? `Masih ada ${needRevision} dokumen yang membutuhkan revisi. Mulai dari dokumen dengan skor terendah agar proses perbaikan lebih terarah.`
            : 'Tidak ada dokumen yang sedang menunggu revisi. Anda bisa membuka Document Library atau mengunggah dokumen baru.';
    }
    renderUserDashboardPriorities(dashboard.revision_priorities || []);
    renderUserDashboardActivities(dashboard.latest_activities || dashboard.latest_documents || []);
};

const loadUserDashboard = async (token) => {
    hideAlert('dashboardAlert');

    try {
        const response = await fetch('/api/user/dashboard', {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            throw new Error(getErrorMessage(data, 'Dashboard tidak dapat dimuat.'));
        }

        renderUserDashboard(data.data || {});
        document.getElementById('dashboardLoading').classList.add('hidden');
        document.getElementById('dashboardContent').classList.remove('hidden');
    } catch (error) {
        document.getElementById('dashboardLoading').classList.add('hidden');
        showAlert(error.message || 'Dashboard tidak dapat dimuat.', 'dashboardAlert');
    }
};

const initializeUserDashboard = () => {
    const session = requireUserSession();

    if (!session) return;

    const userBox = document.getElementById('currentUser');

    if (userBox) {
        userBox.textContent = `${session.user.name} | ${session.user.email}`;
    }

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    loadUserDashboard(session.token);
};

const adminStatusOrder = ['uploaded', 'analyzed', 'need_revision', 'revised', 'ready'];

const createAdminStatusItem = (status, count) => {
    const item = document.createElement('div');
    item.append(
        createTextElement('span', `status-badge status-${status}`, documentStatusLabels[status] || status),
        createTextElement('strong', '', count ?? 0),
    );

    return item;
};

const createAdminDocumentRow = (documentData) => {
    const row = document.createElement('tr');
    const documentCell = document.createElement('td');
    const ownerCell = document.createElement('td');
    const statusCell = document.createElement('td');

    documentCell.append(
        createTextElement('strong', 'document-title', documentData.title || '-'),
        createTextElement('span', 'document-topic', `V${documentData.latest_version?.version_number || '-'}`),
    );
    ownerCell.append(
        createTextElement('strong', 'document-title', documentData.user?.name || '-'),
        createTextElement('span', 'document-topic', documentData.user?.email || '-'),
    );
    statusCell.appendChild(createTextElement(
        'span',
        `status-badge status-${documentData.status}`,
        documentStatusLabels[documentData.status] || documentData.status,
    ));
    row.append(
        documentCell,
        ownerCell,
        createTextElement('td', '', documentData.document_type?.label || '-'),
        statusCell,
        createTextElement('td', 'score-cell', documentData.latest_score ?? '-'),
    );

    return row;
};

const createAdminAnalysisRow = (analysis) => {
    const row = document.createElement('tr');
    const documentCell = document.createElement('td');
    const ownerCell = document.createElement('td');

    documentCell.appendChild(createTextElement('strong', 'document-title', analysis.document?.title || '-'));
    ownerCell.append(
        createTextElement('strong', 'document-title', analysis.document?.user?.name || '-'),
        createTextElement('span', 'document-topic', analysis.document?.user?.email || '-'),
    );
    row.append(
        documentCell,
        ownerCell,
        createTextElement('td', '', `V${analysis.document_version?.version_number || '-'}`),
        createTextElement('td', 'score-cell', analysis.total_score ?? '-'),
        createTextElement('td', '', formatDate(analysis.created_at)),
    );

    return row;
};

const renderAdminTable = (bodyId, items, createRow, emptyMessage, columnCount) => {
    const body = document.getElementById(bodyId);

    if (!items.length) {
        const row = document.createElement('tr');
        const cell = createTextElement('td', 'comparison-no-data', emptyMessage);
        cell.colSpan = columnCount;
        row.appendChild(cell);
        body.replaceChildren(row);
        return;
    }

    body.replaceChildren(...items.map(createRow));
};

const renderAdminDashboard = (dashboard) => {
    const summary = dashboard.summary || {};

    document.getElementById('adminTotalUsers').textContent = summary.total_users ?? 0;
    document.getElementById('adminActiveUsers').textContent = summary.active_users ?? 0;
    document.getElementById('adminInactiveUsers').textContent = summary.inactive_users ?? 0;
    document.getElementById('adminTotalDocuments').textContent = summary.total_documents ?? 0;
    document.getElementById('adminTotalAnalysis').textContent = summary.total_analysis ?? 0;
    document.getElementById('adminAverageScore').textContent = summary.average_score ?? 0;
    document.getElementById('adminArticleCount').textContent = summary.by_type?.article ?? 0;
    document.getElementById('adminProposalCount').textContent = summary.by_type?.proposal ?? 0;
    document.getElementById('adminReportCount').textContent = summary.by_type?.report ?? 0;
    document.getElementById('adminStatusBreakdown').replaceChildren(
        ...adminStatusOrder.map((status) => createAdminStatusItem(status, summary.by_status?.[status])),
    );

    renderAdminTable(
        'adminLatestDocuments',
        dashboard.latest_documents || [],
        createAdminDocumentRow,
        'Belum ada dokumen.',
        5,
    );
    renderAdminTable(
        'adminLatestAnalyses',
        dashboard.latest_analyses || [],
        createAdminAnalysisRow,
        'Belum ada analisis.',
        5,
    );
};

const loadAdminDashboard = async (token) => {
    try {
        const response = await fetch('/api/admin/dashboard', {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (response.status === 403) {
            window.location.href = '/dashboard';
            return;
        }

        if (!response.ok) {
            throw new Error(data.message || 'Dashboard admin tidak dapat dimuat.');
        }

        renderAdminDashboard(data.data || {});
        document.getElementById('adminDashboardLoading').classList.add('hidden');
        document.getElementById('adminDashboardContent').classList.remove('hidden');
    } catch (error) {
        document.getElementById('adminDashboardLoading').classList.add('hidden');
        showAlert(error.message || 'Dashboard admin tidak dapat dimuat.', 'adminDashboardAlert');
    }
};

const initializeAdminDashboard = () => {
    const session = requireUserSession(true);

    if (!session) return;

    if (session.user.role !== 'admin') {
        window.location.href = '/dashboard';
        return;
    }

    document.getElementById('adminIdentity').textContent = session.user.name || 'Admin';
    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    loadAdminDashboard(session.token);
};

let adminUsersPage = 1;
let adminUsersSearchTimer;

const createAdminUserRow = (user, currentUserId, token) => {
    const row = document.createElement('tr');
    const userCell = document.createElement('td');
    const statusCell = document.createElement('td');
    const actionCell = document.createElement('td');
    const actionButton = createTextElement(
        'button',
        user.is_active ? 'admin-user-action admin-user-deactivate' : 'admin-user-action admin-user-activate',
        user.is_active ? 'Nonaktifkan' : 'Aktifkan',
    );

    userCell.append(
        createTextElement('strong', 'document-title', user.name || '-'),
        createTextElement('span', 'document-topic', user.email || '-'),
    );
    statusCell.appendChild(createTextElement(
        'span',
        `admin-user-status admin-user-status-${user.is_active ? 'active' : 'inactive'}`,
        user.is_active ? 'Aktif' : 'Nonaktif',
    ));
    actionButton.type = 'button';
    actionButton.disabled = `${user.id}` === `${currentUserId}` && user.is_active;
    actionButton.title = actionButton.disabled ? 'Admin tidak dapat menonaktifkan akunnya sendiri.' : '';
    actionButton.addEventListener('click', () => updateAdminUserStatus(user, !user.is_active, token));
    actionCell.appendChild(actionButton);
    row.append(
        userCell,
        createTextElement('td', 'uppercase-cell', user.role || '-'),
        createTextElement('td', 'score-cell', user.documents_count ?? 0),
        statusCell,
        createTextElement('td', '', formatDate(user.created_at)),
        actionCell,
    );

    return row;
};

const renderAdminUsers = (pagination, currentUserId, token) => {
    const users = pagination.data || [];
    const body = document.getElementById('adminUserTableBody');

    document.getElementById('adminUserVisibleCount').textContent = users.length;
    document.getElementById('adminUserTotalCount').textContent = pagination.total ?? 0;
    document.getElementById('adminUserCurrentPage').textContent = pagination.current_page ?? 1;
    document.getElementById('adminUserLastPage').textContent = pagination.last_page ?? 1;
    document.getElementById('adminUsersPageLabel').textContent = `Halaman ${pagination.current_page ?? 1}`;
    document.getElementById('adminUsersPreviousPage').disabled = !pagination.prev_page_url;
    document.getElementById('adminUsersNextPage').disabled = !pagination.next_page_url;

    if (users.length === 0) {
        const row = document.createElement('tr');
        const cell = createTextElement('td', 'comparison-no-data', 'Tidak ada user yang sesuai dengan filter.');
        cell.colSpan = 6;
        row.appendChild(cell);
        body.replaceChildren(row);
        return;
    }

    body.replaceChildren(...users.map((user) => createAdminUserRow(user, currentUserId, token)));
};

const loadAdminUsers = async (token, currentUserId, page = adminUsersPage) => {
    const query = new URLSearchParams({
        page,
        per_page: 15,
    });
    const search = document.getElementById('adminUserSearch').value.trim();
    const role = document.getElementById('adminUserRoleFilter').value;
    const status = document.getElementById('adminUserStatusFilter').value;

    if (search) query.set('search', search);
    if (role) query.set('role', role);
    if (status !== '') query.set('is_active', status);

    hideAlert('adminUsersAlert');

    try {
        const response = await fetch(`/api/admin/users?${query}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (response.status === 403) {
            window.location.href = '/dashboard';
            return;
        }

        if (!response.ok) {
            throw new Error(getErrorMessage(data, 'Data user tidak dapat dimuat.'));
        }

        adminUsersPage = data.data?.current_page || 1;
        renderAdminUsers(data.data || {}, currentUserId, token);
        document.getElementById('adminUsersLoading').classList.add('hidden');
        document.getElementById('adminUsersContent').classList.remove('hidden');
    } catch (error) {
        document.getElementById('adminUsersLoading').classList.add('hidden');
        showAlert(error.message || 'Data user tidak dapat dimuat.', 'adminUsersAlert');
    }
};

const updateAdminUserStatus = async (user, isActive, token) => {
    hideAlert('adminUsersAlert');

    try {
        const response = await fetch(`/api/admin/users/${user.id}/status`, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ is_active: isActive }),
        });
        const data = await response.json();

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Status user gagal diperbarui.'), 'adminUsersAlert');
            return;
        }

        await loadAdminUsers(token, storage.getUser()?.id, adminUsersPage);
        showAlert(data.message || 'Status user berhasil diperbarui.', 'adminUsersAlert', 'success');
    } catch {
        showAlert('Status user tidak dapat diperbarui. Silakan coba kembali.', 'adminUsersAlert');
    }
};

const initializeAdminUsers = () => {
    const session = requireUserSession(true);

    if (!session) return;

    if (session.user.role !== 'admin') {
        window.location.href = '/dashboard';
        return;
    }

    const reloadFromFirstPage = () => {
        adminUsersPage = 1;
        loadAdminUsers(session.token, session.user.id, adminUsersPage);
    };

    document.getElementById('adminUsersIdentity').textContent = session.user.name || 'Admin';
    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    document.getElementById('adminUserSearch')?.addEventListener('input', () => {
        window.clearTimeout(adminUsersSearchTimer);
        adminUsersSearchTimer = window.setTimeout(reloadFromFirstPage, 350);
    });
    document.getElementById('adminUserRoleFilter')?.addEventListener('change', reloadFromFirstPage);
    document.getElementById('adminUserStatusFilter')?.addEventListener('change', reloadFromFirstPage);
    document.getElementById('resetAdminUserFilters')?.addEventListener('click', () => {
        document.getElementById('adminUserSearch').value = '';
        document.getElementById('adminUserRoleFilter').value = '';
        document.getElementById('adminUserStatusFilter').value = '';
        reloadFromFirstPage();
    });
    document.getElementById('adminUsersPreviousPage')?.addEventListener('click', () => {
        if (adminUsersPage > 1) loadAdminUsers(session.token, session.user.id, adminUsersPage - 1);
    });
    document.getElementById('adminUsersNextPage')?.addEventListener('click', () => {
        loadAdminUsers(session.token, session.user.id, adminUsersPage + 1);
    });
    loadAdminUsers(session.token, session.user.id);
};

let adminDocumentsPage = 1;
let adminDocumentsSearchTimer;

const createAdminDocumentManagementRow = (documentData) => {
    const row = document.createElement('tr');
    const documentCell = document.createElement('td');
    const ownerCell = document.createElement('td');
    const statusCell = document.createElement('td');
    const uploadDate = documentData.latest_version?.uploaded_at || documentData.created_at;

    documentCell.append(
        createTextElement('strong', 'document-title', documentData.title || '-'),
        createTextElement('span', 'document-topic', documentData.topic || 'Tanpa topik'),
    );
    ownerCell.append(
        createTextElement('strong', 'document-title', documentData.user?.name || '-'),
        createTextElement('span', 'document-topic', documentData.user?.email || '-'),
    );
    statusCell.appendChild(createTextElement(
        'span',
        `status-badge status-${documentData.status}`,
        documentStatusLabels[documentData.status] || documentData.status || '-',
    ));
    row.append(
        documentCell,
        ownerCell,
        createTextElement('td', '', documentData.document_type?.label || '-'),
        statusCell,
        createTextElement('td', 'score-cell', documentData.latest_score ?? '-'),
        createTextElement('td', '', `V${documentData.versions_count || 1}`),
        createTextElement('td', '', formatDate(uploadDate)),
    );

    return row;
};

const renderAdminDocuments = (pagination) => {
    const documents = pagination.data || [];
    const body = document.getElementById('adminDocumentTableBody');

    document.getElementById('adminDocumentVisibleCount').textContent = documents.length;
    document.getElementById('adminDocumentTotalCount').textContent = pagination.total ?? 0;
    document.getElementById('adminDocumentCurrentPage').textContent = pagination.current_page ?? 1;
    document.getElementById('adminDocumentLastPage').textContent = pagination.last_page ?? 1;
    document.getElementById('adminDocumentsPageLabel').textContent = `Halaman ${pagination.current_page ?? 1}`;
    document.getElementById('adminDocumentsPreviousPage').disabled = !pagination.prev_page_url;
    document.getElementById('adminDocumentsNextPage').disabled = !pagination.next_page_url;

    if (documents.length === 0) {
        const row = document.createElement('tr');
        const cell = createTextElement('td', 'comparison-no-data', 'Tidak ada dokumen yang sesuai dengan filter.');
        cell.colSpan = 7;
        row.appendChild(cell);
        body.replaceChildren(row);
        return;
    }

    body.replaceChildren(...documents.map(createAdminDocumentManagementRow));
};

const loadAdminDocuments = async (token, page = adminDocumentsPage) => {
    const query = new URLSearchParams({
        page,
        per_page: 15,
    });
    const search = document.getElementById('adminDocumentSearch').value.trim();
    const type = document.getElementById('adminDocumentTypeFilter').value;
    const status = document.getElementById('adminDocumentStatusFilter').value;

    if (search) query.set('search', search);
    if (type) query.set('document_type', type);
    if (status) query.set('status', status);

    hideAlert('adminDocumentsAlert');

    try {
        const response = await fetch(`/api/admin/documents?${query}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (response.status === 403) {
            window.location.href = '/dashboard';
            return;
        }

        if (!response.ok) {
            throw new Error(getErrorMessage(data, 'Data dokumen tidak dapat dimuat.'));
        }

        adminDocumentsPage = data.data?.current_page || 1;
        renderAdminDocuments(data.data || {});
        document.getElementById('adminDocumentsLoading').classList.add('hidden');
        document.getElementById('adminDocumentsContent').classList.remove('hidden');
    } catch (error) {
        document.getElementById('adminDocumentsLoading').classList.add('hidden');
        showAlert(error.message || 'Data dokumen tidak dapat dimuat.', 'adminDocumentsAlert');
    }
};

const initializeAdminDocuments = () => {
    const session = requireUserSession(true);

    if (!session) return;

    if (session.user.role !== 'admin') {
        window.location.href = '/dashboard';
        return;
    }

    const reloadFromFirstPage = () => {
        adminDocumentsPage = 1;
        loadAdminDocuments(session.token, adminDocumentsPage);
    };

    document.getElementById('adminDocumentsIdentity').textContent = session.user.name || 'Admin';
    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    document.getElementById('adminDocumentSearch')?.addEventListener('input', () => {
        window.clearTimeout(adminDocumentsSearchTimer);
        adminDocumentsSearchTimer = window.setTimeout(reloadFromFirstPage, 350);
    });
    document.getElementById('adminDocumentTypeFilter')?.addEventListener('change', reloadFromFirstPage);
    document.getElementById('adminDocumentStatusFilter')?.addEventListener('change', reloadFromFirstPage);
    document.getElementById('resetAdminDocumentFilters')?.addEventListener('click', () => {
        document.getElementById('adminDocumentSearch').value = '';
        document.getElementById('adminDocumentTypeFilter').value = '';
        document.getElementById('adminDocumentStatusFilter').value = '';
        reloadFromFirstPage();
    });
    document.getElementById('adminDocumentsPreviousPage')?.addEventListener('click', () => {
        if (adminDocumentsPage > 1) loadAdminDocuments(session.token, adminDocumentsPage - 1);
    });
    document.getElementById('adminDocumentsNextPage')?.addEventListener('click', () => {
        loadAdminDocuments(session.token, adminDocumentsPage + 1);
    });
    loadAdminDocuments(session.token);
};

let adminJournalsPage = 1;
let adminJournalsSearchTimer;
let adminJournals = [];
const minimumJournalEligibilityScore = 70;

const hasJournalValue = (value) => {
    const normalizedValue = `${value ?? ''}`.trim().toLocaleLowerCase('id');

    return normalizedValue !== ''
        && !['nan', 'null', 'undefined', '-', '#'].includes(normalizedValue);
};

const isJournalAiReady = (journal) => {
    const hasEligibleScore = Number(journal.eligibility_score ?? 0) >= minimumJournalEligibilityScore;
    const isActive = journal.is_active === true;
    const isVerified = journal.verification_status === 'verified';

    return hasEligibleScore && isActive && isVerified;
};

const getJournalMissingFields = (journal) => {
    const missing = [];

    if (!hasJournalValue(journal.name)) missing.push('Nama jurnal');
    if (!hasJournalValue(journal.sinta_level)) missing.push('SINTA level');
    if (!hasJournalValue(journal.subject_area)) missing.push('Subject area');
    if (!getSafeExternalUrl(journal.website_url)) missing.push('Website URL');

    if (!hasJournalValue(journal.focus_scope) && !hasJournalValue(journal.keywords)) {
        missing.push('Focus scope atau keywords');
    }

    if (journal.is_active !== true) missing.push('Status aktif');
    if (journal.verification_status !== 'verified') missing.push('Verification status');
    if (Number(journal.eligibility_score ?? 0) < minimumJournalEligibilityScore) {
        missing.push(`Eligibility score minimal ${minimumJournalEligibilityScore}`);
    }

    return missing;
};

const createJournalAiReadyBadge = (journal) => createTextElement(
    'span',
    isJournalAiReady(journal)
        ? 'admin-user-status admin-user-status-active'
        : 'admin-user-status admin-user-status-inactive',
    isJournalAiReady(journal) ? 'AI Ready' : 'Belum Lengkap',
);

const createJournalEligibilityBadge = (journal) => createTextElement(
    'span',
    Number(journal.eligibility_score ?? 0) >= minimumJournalEligibilityScore
        ? 'admin-user-status admin-user-status-active'
        : 'admin-user-status admin-user-status-inactive',
    `Eligibility ${journal.eligibility_score ?? 0}/100`,
);

const getSafeExternalUrl = (value) => {
    if (!value) return null;

    const normalizedValue = `${value}`.trim();

    if (!normalizedValue || ['nan', 'null', 'undefined', '-', '#'].includes(normalizedValue.toLocaleLowerCase('id'))) {
        return null;
    }

    if (!/^https?:\/\//i.test(normalizedValue)) {
        return null;
    }

    try {
        const url = new URL(normalizedValue);

        return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
    } catch {
        return null;
    }
};

const setAdminJournalStatValue = (id, value) => {
    const element = document.getElementById(id);

    if (element) element.textContent = value ?? 0;
};

const renderAdminJournalSintaStats = (items = []) => {
    const container = document.getElementById('adminJournalSintaStats');

    if (!container) return;

    const totals = new Map(items.map((item) => [item.sinta_level || 'Belum diisi', item.total ?? 0]));
    const orderedLevels = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6'];
    const extraLevels = items
        .map((item) => item.sinta_level || 'Belum diisi')
        .filter((level) => !orderedLevels.includes(level));
    const levels = [...orderedLevels, ...new Set(extraLevels)];

    container.replaceChildren(...levels.map((level) => {
        const item = document.createElement('article');
        item.className = 'admin-journal-sinta-item';
        item.append(
            createTextElement('span', '', level),
            createTextElement('strong', '', totals.get(level) ?? 0),
        );

        return item;
    }));
};

const loadAdminJournalStats = async (token) => {
    try {
        const response = await fetch('/api/admin/journals/stats', {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (response.status === 403) {
            window.location.href = '/dashboard';
            return;
        }

        if (!response.ok) {
            throw new Error(getErrorMessage(data, 'Statistik jurnal tidak dapat dimuat.'));
        }

        const stats = data.data || {};
        setAdminJournalStatValue('adminJournalStatTotal', stats.total);
        setAdminJournalStatValue('adminJournalStatActive', stats.active);
        setAdminJournalStatValue('adminJournalStatAiReady', stats.ai_ready);
        setAdminJournalStatValue('adminJournalStatPending', stats.pending_review);
        setAdminJournalStatValue('adminJournalStatVerified', stats.verified);
        renderAdminJournalSintaStats(stats.by_sinta || []);
    } catch (error) {
        showAlert(error.message || 'Statistik jurnal tidak dapat dimuat.', 'adminJournalsAlert');
    }
};

const createAdminJournalRow = (journal, token) => {
    const row = document.createElement('tr');
    const journalCell = document.createElement('td');
    const sintaCell = document.createElement('td');
    const websiteCell = document.createElement('td');
    const statusCell = document.createElement('td');
    const actionCell = document.createElement('td');
    const issnText = `E-ISSN: ${journal.e_issn || '-'} | P-ISSN: ${journal.p_issn || '-'}`;
    const safeWebsiteUrl = getSafeExternalUrl(journal.website_url);
    const actionButton = createTextElement(
        'button',
        journal.is_active ? 'admin-user-action admin-user-deactivate' : 'admin-user-action admin-user-activate',
        journal.is_active ? 'Nonaktifkan' : 'Aktifkan',
    );
    const editButton = createTextElement('button', 'admin-user-action admin-journal-edit-action', 'Edit');
    const detailButton = createTextElement('button', 'admin-user-action admin-journal-detail-action', 'Detail');

    journalCell.append(
        createTextElement('strong', 'document-title', journal.name || '-'),
        createTextElement('span', 'document-topic', journal.publisher || 'Publisher belum tersedia'),
        createTextElement('span', 'document-topic', issnText),
    );
    sintaCell.appendChild(createTextElement('span', 'status-badge', journal.sinta_level || '-'));

    if (safeWebsiteUrl) {
        const link = createTextElement('a', 'detail-link admin-journal-link', 'Buka');
        link.href = safeWebsiteUrl;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        websiteCell.appendChild(link);
    } else {
        websiteCell.textContent = '-';
    }

    statusCell.className = 'admin-journal-status-cell';
    statusCell.append(
        createTextElement(
            'span',
            `admin-user-status admin-user-status-${journal.is_active ? 'active' : 'inactive'}`,
            journal.is_active ? 'Aktif' : 'Nonaktif',
        ),
        createJournalAiReadyBadge(journal),
        createJournalEligibilityBadge(journal),
        createTextElement('span', 'document-topic', journal.verification_status || '-'),
    );
    editButton.type = 'button';
    editButton.addEventListener('click', () => openAdminJournalEditModal(journal, token));
    detailButton.type = 'button';
    detailButton.addEventListener('click', () => showAdminJournalDetail(journal));
    actionButton.type = 'button';
    actionButton.addEventListener('click', () => updateAdminJournalStatus(journal, !journal.is_active, token));
    actionCell.className = 'admin-journal-action-cell';
    actionCell.append(editButton, detailButton, actionButton);
    row.append(
        journalCell,
        sintaCell,
        createTextElement('td', '', journal.subject_area || '-'),
        websiteCell,
        statusCell,
        actionCell,
    );

    return row;
};

const showAdminJournalDetail = (journal) => {
    if (!journal) {
        showAlert('Data jurnal tidak ditemukan.', 'adminJournalsAlert');
        return;
    }

    const missingFields = getJournalMissingFields(journal);

    window.alert(
        `Nama: ${journal.name || '-'}\n\n`
        + `Publisher: ${journal.publisher || '-'}\n\n`
        + `SINTA: ${journal.sinta_level || '-'}\n\n`
        + `Subject: ${journal.subject_area || '-'}\n\n`
        + `Keywords: ${journal.keywords || '-'}\n\n`
        + `Focus Scope: ${journal.focus_scope || '-'}\n\n`
        + `Website: ${journal.website_url || '-'}\n\n`
        + `Eligibility Score: ${journal.eligibility_score ?? 0}/100\n\n`
        + `Status AI: ${isJournalAiReady(journal) ? 'AI Ready' : 'Belum Lengkap'}\n\n`
        + `Data yang kurang:\n${missingFields.length ? `- ${missingFields.join('\n- ')}` : '- Tidak ada'}`,
    );
};

const renderAdminJournals = (pagination, token) => {
    const journals = pagination.data || [];
    const body = document.getElementById('adminJournalTableBody');
    adminJournals = journals;

    document.getElementById('adminJournalVisibleCount').textContent = journals.length;
    document.getElementById('adminJournalTotalCount').textContent = pagination.total ?? 0;
    document.getElementById('adminJournalCurrentPage').textContent = pagination.current_page ?? 1;
    document.getElementById('adminJournalLastPage').textContent = pagination.last_page ?? 1;
    document.getElementById('adminJournalsPageLabel').textContent = `Halaman ${pagination.current_page ?? 1}`;
    document.getElementById('adminJournalsPreviousPage').disabled = !pagination.prev_page_url;
    document.getElementById('adminJournalsNextPage').disabled = !pagination.next_page_url;

    if (journals.length === 0) {
        const row = document.createElement('tr');
        const cell = createTextElement('td', 'comparison-no-data', 'Belum ada data jurnal.');
        cell.colSpan = 6;
        row.appendChild(cell);
        body.replaceChildren(row);
        return;
    }

    body.replaceChildren(...journals.map((journal) => createAdminJournalRow(journal, token)));
};

const getAdminJournalModal = () => document.getElementById('adminJournalEditModal');

const closeAdminJournalEditModal = () => {
    getAdminJournalModal()?.classList.add('hidden');
    hideAlert('adminJournalEditAlert');
};

const setAdminJournalFieldValue = (id, value) => {
    const field = document.getElementById(id);

    if (field) field.value = value ?? '';
};

const openAdminJournalEditModal = (journal, token) => {
    const modal = getAdminJournalModal();
    const form = document.getElementById('adminJournalEditForm');

    if (!modal || !form) return;

    form.dataset.token = token;
    setAdminJournalFieldValue('editJournalId', journal.id);
    setAdminJournalFieldValue('editJournalName', journal.name);
    setAdminJournalFieldValue('editJournalPublisher', journal.publisher);
    setAdminJournalFieldValue('editJournalSintaLevel', journal.sinta_level);
    setAdminJournalFieldValue('editJournalSubjectArea', journal.subject_area);
    setAdminJournalFieldValue('editJournalFocusScope', journal.focus_scope);
    setAdminJournalFieldValue('editJournalKeywords', journal.keywords);
    setAdminJournalFieldValue('editJournalWebsiteUrl', getSafeExternalUrl(journal.website_url) ? journal.website_url : '');
    setAdminJournalFieldValue('editJournalTemplateUrl', getSafeExternalUrl(journal.template_url) ? journal.template_url : '');
    setAdminJournalFieldValue(
        'editJournalAuthorGuidelineUrl',
        getSafeExternalUrl(journal.author_guideline_url) ? journal.author_guideline_url : '',
    );
    setAdminJournalFieldValue('editJournalIsActive', journal.is_active ? 'true' : 'false');
    setAdminJournalFieldValue('editJournalVerificationStatus', journal.verification_status || 'pending_review');
    hideAlert('adminJournalEditAlert');
    modal.classList.remove('hidden');
};

const nullableString = (value) => {
    const normalizedValue = `${value ?? ''}`.trim();

    return normalizedValue === '' ? null : normalizedValue;
};

const submitAdminJournalEdit = async (form) => {
    const token = form.dataset.token;
    const journalId = document.getElementById('editJournalId')?.value;

    hideAlert('adminJournalEditAlert');

    if (!token || !journalId) {
        showAlert('Data jurnal tidak ditemukan.', 'adminJournalEditAlert');
        return;
    }

    if (!form.reportValidity()) return;

    setSubmitting(form, true);

    try {
        const payload = {
            name: nullableString(document.getElementById('editJournalName').value),
            publisher: nullableString(document.getElementById('editJournalPublisher').value),
            sinta_level: nullableString(document.getElementById('editJournalSintaLevel').value),
            subject_area: nullableString(document.getElementById('editJournalSubjectArea').value),
            focus_scope: nullableString(document.getElementById('editJournalFocusScope').value),
            keywords: nullableString(document.getElementById('editJournalKeywords').value),
            website_url: nullableString(document.getElementById('editJournalWebsiteUrl').value),
            template_url: nullableString(document.getElementById('editJournalTemplateUrl').value),
            author_guideline_url: nullableString(document.getElementById('editJournalAuthorGuidelineUrl').value),
            is_active: document.getElementById('editJournalIsActive').value === 'true',
            verification_status: document.getElementById('editJournalVerificationStatus').value,
        };
        const response = await fetch(`/api/admin/journals/${journalId}`, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Data jurnal gagal diperbarui.'), 'adminJournalEditAlert');
            return;
        }

        closeAdminJournalEditModal();
        await loadAdminJournals(token, adminJournalsPage);
        await loadAdminJournalStats(token);
        showAlert(data.message || 'Data jurnal berhasil diperbarui.', 'adminJournalsAlert', 'success');
    } catch {
        showAlert('Data jurnal tidak dapat diperbarui. Silakan coba kembali.', 'adminJournalEditAlert');
    } finally {
        setSubmitting(form, false);
    }
};

const loadAdminJournals = async (token, page = adminJournalsPage) => {
    const query = new URLSearchParams({ page });
    const search = document.getElementById('adminJournalSearch').value.trim();
    const sintaLevel = document.getElementById('adminJournalSintaFilter').value;
    const isActive = document.getElementById('adminJournalActiveFilter').value;
    const verificationStatus = document.getElementById('adminJournalVerificationFilter').value;

    if (search) query.set('search', search);
    if (sintaLevel) query.set('sinta_level', sintaLevel);
    if (isActive) query.set('is_active', isActive);
    if (verificationStatus) query.set('verification_status', verificationStatus);

    hideAlert('adminJournalsAlert');

    try {
        const response = await fetch(`/api/admin/journals?${query}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (response.status === 403) {
            window.location.href = '/dashboard';
            return;
        }

        if (!response.ok) {
            throw new Error(getErrorMessage(data, 'Data jurnal tidak dapat dimuat.'));
        }

        adminJournalsPage = data.data?.current_page || 1;
        renderAdminJournals(data.data || {}, token);
        document.getElementById('adminJournalsLoading').classList.add('hidden');
        document.getElementById('adminJournalsContent').classList.remove('hidden');
    } catch (error) {
        document.getElementById('adminJournalsLoading').classList.add('hidden');
        showAlert(error.message || 'Data jurnal tidak dapat dimuat.', 'adminJournalsAlert');
    }
};

const importAdminJournals = async (form, token) => {
    hideAlert('adminJournalsAlert');

    if (!form.reportValidity()) return;

    setSubmitting(form, true);

    try {
        const response = await fetch('/api/admin/journals/import', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
            body: new FormData(form),
        });
        const data = await response.json();

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Import CSV jurnal gagal.'), 'adminJournalsAlert');
            return;
        }

        form.reset();
        document.getElementById('adminJournalFileField')?.classList.remove('has-file');
        document.getElementById('adminJournalFileName').textContent = 'Pilih file CSV jurnal';
        adminJournalsPage = 1;
        await loadAdminJournals(token, adminJournalsPage);
        await loadAdminJournalStats(token);
        showAlert(
            `${data.message || 'Import CSV jurnal selesai.'} Imported: ${data.summary?.imported ?? 0}, Updated: ${data.summary?.updated ?? 0}, Failed: ${data.summary?.failed ?? 0}.`,
            'adminJournalsAlert',
            'success',
        );
    } catch {
        showAlert('Import CSV jurnal tidak dapat diproses. Silakan coba kembali.', 'adminJournalsAlert');
    } finally {
        setSubmitting(form, false);
    }
};

const updateAdminJournalStatus = async (journal, isActive, token) => {
    hideAlert('adminJournalsAlert');

    if (isActive === true) {
        const missingFields = getJournalMissingFields({
            ...journal,
            is_active: true,
            verification_status: 'verified',
        });

        if (missingFields.length > 0) {
            const confirmActivate = window.confirm(
                `Data jurnal belum lengkap:\n\n- ${missingFields.join('\n- ')}\n\nTetap aktifkan jurnal ini?`,
            );

            if (!confirmActivate) return;
        }
    }

    try {
        const response = await fetch(`/api/admin/journals/${journal.id}`, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                is_active: isActive,
                verification_status: isActive ? 'verified' : 'pending_review',
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Status jurnal gagal diperbarui.'), 'adminJournalsAlert');
            return;
        }

        await loadAdminJournals(token, adminJournalsPage);
        await loadAdminJournalStats(token);
        showAlert(data.message || 'Status jurnal berhasil diperbarui.', 'adminJournalsAlert', 'success');
    } catch {
        showAlert('Status jurnal tidak dapat diperbarui. Silakan coba kembali.', 'adminJournalsAlert');
    }
};

const initializeAdminJournals = () => {
    const session = requireUserSession(true);

    if (!session) return;

    if (session.user.role !== 'admin') {
        window.location.href = '/dashboard';
        return;
    }

    const reloadFromFirstPage = () => {
        adminJournalsPage = 1;
        loadAdminJournals(session.token, adminJournalsPage);
    };
    const importForm = document.getElementById('adminJournalImportForm');
    const editForm = document.getElementById('adminJournalEditForm');
    const fileInput = document.getElementById('adminJournalCsvFile');

    document.getElementById('adminJournalsIdentity').textContent = session.user.name || 'Admin';
    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    document.getElementById('adminJournalSearch')?.addEventListener('input', () => {
        window.clearTimeout(adminJournalsSearchTimer);
        adminJournalsSearchTimer = window.setTimeout(reloadFromFirstPage, 350);
    });
    document.getElementById('adminJournalSintaFilter')?.addEventListener('change', reloadFromFirstPage);
    document.getElementById('adminJournalActiveFilter')?.addEventListener('change', reloadFromFirstPage);
    document.getElementById('adminJournalVerificationFilter')?.addEventListener('change', reloadFromFirstPage);
    document.getElementById('resetAdminJournalFilters')?.addEventListener('click', () => {
        document.getElementById('adminJournalSearch').value = '';
        document.getElementById('adminJournalSintaFilter').value = '';
        document.getElementById('adminJournalActiveFilter').value = '';
        document.getElementById('adminJournalVerificationFilter').value = '';
        reloadFromFirstPage();
    });
    document.getElementById('adminJournalsPreviousPage')?.addEventListener('click', () => {
        if (adminJournalsPage > 1) loadAdminJournals(session.token, adminJournalsPage - 1);
    });
    document.getElementById('adminJournalsNextPage')?.addEventListener('click', () => {
        loadAdminJournals(session.token, adminJournalsPage + 1);
    });
    fileInput?.addEventListener('change', () => {
        const fileName = fileInput.files?.[0]?.name || 'Pilih file CSV jurnal';
        document.getElementById('adminJournalFileName').textContent = fileName;
        document.getElementById('adminJournalFileField')?.classList.toggle('has-file', Boolean(fileInput.files?.length));
    });
    importForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        importAdminJournals(importForm, session.token);
    });
    editForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        submitAdminJournalEdit(editForm);
    });
    document.getElementById('closeAdminJournalEditModal')?.addEventListener('click', closeAdminJournalEditModal);
    document.getElementById('cancelAdminJournalEdit')?.addEventListener('click', closeAdminJournalEditModal);
    getAdminJournalModal()?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) closeAdminJournalEditModal();
    });
    loadAdminJournalStats(session.token);
    loadAdminJournals(session.token);
};

let adminRubrics = [];

const rubricTypeLabels = {
    article: 'Artikel ilmiah',
    proposal: 'Proposal',
    report: 'Laporan',
};

const createRubricInput = (type, className, value, attributes = {}) => {
    const input = document.createElement(type === 'textarea' ? 'textarea' : 'input');
    input.className = className;

    if (type === 'textarea') {
        input.rows = attributes.rows || 3;
        input.value = value || '';
    } else if (type === 'checkbox') {
        input.type = 'checkbox';
        input.checked = Boolean(value);
    } else {
        input.type = type;
        input.value = value ?? '';
    }

    Object.entries(attributes).forEach(([key, attributeValue]) => {
        if (key !== 'rows') input.setAttribute(key, attributeValue);
    });

    return input;
};

const createAdminRubricRow = (rubric, token) => {
    const row = document.createElement('tr');
    const aspectCell = document.createElement('td');
    const weightCell = document.createElement('td');
    const descriptionCell = document.createElement('td');
    const statusCell = document.createElement('td');
    const actionCell = document.createElement('td');
    const aspectInput = createRubricInput('text', 'admin-rubric-aspect-input', rubric.aspect_name || '', {
        maxlength: 255,
    });
    const weightInput = createRubricInput('number', 'admin-rubric-weight-input', rubric.weight ?? 0, {
        min: 0,
        max: 100,
        step: 1,
    });
    const descriptionInput = createRubricInput('textarea', 'admin-rubric-description-input', rubric.description || '');
    const activeInput = createRubricInput('checkbox', 'admin-rubric-active-input', rubric.is_active);
    const saveButton = createTextElement('button', 'admin-user-action admin-user-activate', 'Simpan');

    aspectInput.name = 'aspect_name';
    weightInput.name = 'weight';
    descriptionInput.name = 'description';
    activeInput.name = 'is_active';
    saveButton.type = 'button';
    saveButton.addEventListener('click', () => updateAdminRubric(
        rubric.id,
        {
            aspect_name: aspectInput.value.trim(),
            weight: Number.parseInt(weightInput.value, 10),
            description: descriptionInput.value.trim() || null,
            is_active: activeInput.checked,
        },
        token,
        saveButton,
    ));

    aspectCell.appendChild(aspectInput);
    weightCell.appendChild(weightInput);
    descriptionCell.appendChild(descriptionInput);
    statusCell.append(
        activeInput,
        createTextElement('span', 'admin-rubric-status-label', rubric.is_active ? 'Aktif' : 'Nonaktif'),
    );
    actionCell.appendChild(saveButton);
    row.append(
        createTextElement('td', '', rubricTypeLabels[rubric.document_type?.name] || rubric.document_type?.label || '-'),
        aspectCell,
        weightCell,
        descriptionCell,
        statusCell,
        actionCell,
    );

    return row;
};

const getFilteredAdminRubrics = () => {
    const type = document.getElementById('adminRubricTypeFilter').value;
    const status = document.getElementById('adminRubricStatusFilter').value;

    return adminRubrics.filter((rubric) => {
        const matchesType = !type || rubric.document_type?.name === type;
        const matchesStatus = !status
            || (status === 'active' && rubric.is_active)
            || (status === 'inactive' && !rubric.is_active);

        return matchesType && matchesStatus;
    });
};

const renderAdminRubrics = (token) => {
    const filtered = getFilteredAdminRubrics();
    const body = document.getElementById('adminRubricTableBody');
    const visibleWeight = filtered.reduce((total, rubric) => total + Number(rubric.weight || 0), 0);

    document.getElementById('adminRubricVisibleCount').textContent = filtered.length;
    document.getElementById('adminRubricTotalCount').textContent = adminRubrics.length;
    document.getElementById('adminRubricVisibleWeight').textContent = visibleWeight;

    if (filtered.length === 0) {
        const row = document.createElement('tr');
        const cell = createTextElement('td', 'comparison-no-data', 'Tidak ada rubrik yang sesuai dengan filter.');
        cell.colSpan = 6;
        row.appendChild(cell);
        body.replaceChildren(row);
        return;
    }

    body.replaceChildren(...filtered.map((rubric) => createAdminRubricRow(rubric, token)));
};

const loadAdminRubrics = async (token) => {
    hideAlert('adminRubricsAlert');

    try {
        const response = await fetch('/api/rubrics', {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            throw new Error(getErrorMessage(data, 'Rubrik AI tidak dapat dimuat.'));
        }

        adminRubrics = data.data || [];
        renderAdminRubrics(token);
        document.getElementById('adminRubricsLoading').classList.add('hidden');
        document.getElementById('adminRubricsContent').classList.remove('hidden');
    } catch (error) {
        document.getElementById('adminRubricsLoading').classList.add('hidden');
        showAlert(error.message || 'Rubrik AI tidak dapat dimuat.', 'adminRubricsAlert');
    }
};

const updateAdminRubric = async (rubricId, payload, token, button) => {
    hideAlert('adminRubricsAlert');

    if (!payload.aspect_name) {
        showAlert('Nama aspek rubrik wajib diisi.', 'adminRubricsAlert');
        return;
    }

    if (Number.isNaN(payload.weight)) {
        showAlert('Bobot rubrik harus berupa angka.', 'adminRubricsAlert');
        return;
    }

    button.disabled = true;
    button.textContent = 'Menyimpan';

    try {
        const response = await fetch(`/api/admin/rubrics/${rubricId}`, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (response.status === 403) {
            window.location.href = '/dashboard';
            return;
        }

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Rubrik gagal diperbarui.'), 'adminRubricsAlert');
            return;
        }

        adminRubrics = adminRubrics.map((rubric) => (`${rubric.id}` === `${rubricId}` ? data.data : rubric));
        renderAdminRubrics(token);
        showAlert(data.message || 'Rubrik berhasil diperbarui.', 'adminRubricsAlert', 'success');
    } catch {
        showAlert('Rubrik tidak dapat diperbarui. Silakan coba kembali.', 'adminRubricsAlert');
    } finally {
        button.disabled = false;
        button.textContent = 'Simpan';
    }
};

const initializeAdminRubrics = () => {
    const session = requireUserSession(true);

    if (!session) return;

    if (session.user.role !== 'admin') {
        window.location.href = '/dashboard';
        return;
    }

    document.getElementById('adminRubricsIdentity').textContent = session.user.name || 'Admin';
    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    document.getElementById('adminRubricTypeFilter')?.addEventListener('change', () => renderAdminRubrics(session.token));
    document.getElementById('adminRubricStatusFilter')?.addEventListener('change', () => renderAdminRubrics(session.token));
    document.getElementById('resetAdminRubricFilters')?.addEventListener('click', () => {
        document.getElementById('adminRubricTypeFilter').value = '';
        document.getElementById('adminRubricStatusFilter').value = '';
        renderAdminRubrics(session.token);
    });
    loadAdminRubrics(session.token);
};

const populateDocumentTypes = async (token) => {
    const select = document.getElementById('documentTypeSelect');

    try {
        const response = await fetch('/api/document-types', {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            throw new Error(data?.message);
        }

        select.innerHTML = '<option value="">Pilih jenis dokumen</option>';

        data.data.forEach((type) => {
            const option = document.createElement('option');
            option.value = type.id;
            option.textContent = type.label;
            select.appendChild(option);
        });

        select.disabled = false;
    } catch {
        select.innerHTML = '<option value="">Jenis dokumen gagal dimuat</option>';
        showAlert('Jenis dokumen tidak dapat dimuat. Silakan muat ulang halaman.', 'uploadAlert');
    }
};

const updateSelectedFile = (file) => {
    const label = document.getElementById('fileLabel');
    const meta = document.getElementById('fileMeta');
    const area = document.getElementById('fileDropArea');

    if (!file) {
        label.textContent = 'Pilih file PDF atau DOCX';
        meta.textContent = 'Ukuran maksimal 10 MB';
        area.classList.remove('has-file');
        return;
    }

    label.textContent = file.name;
    meta.textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;
    area.classList.add('has-file');
};

const validateDocumentFile = (file) => {
    if (!file) return 'Pilih file dokumen terlebih dahulu.';

    const extension = file.name.split('.').pop()?.toLowerCase();

    if (!['pdf', 'docx'].includes(extension)) {
        return 'Format file harus PDF atau DOCX.';
    }

    if (file.size > 10 * 1024 * 1024) {
        return 'Ukuran file maksimal 10 MB.';
    }

    return null;
};

const submitDocumentUpload = async (form, token) => {
    hideAlert('uploadAlert');

    if (!form.reportValidity()) return;

    const fileError = validateDocumentFile(form.elements.file.files[0]);

    if (fileError) {
        showAlert(fileError, 'uploadAlert');
        return;
    }

    setSubmitting(form, true);

    try {
        const response = await fetch('/api/documents', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
            body: new FormData(form),
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Upload dokumen gagal.'), 'uploadAlert');
            return;
        }

        showAlert('Dokumen berhasil diunggah. Membuka document library...', 'uploadAlert', 'success');
        window.setTimeout(() => {
            window.location.href = '/documents';
        }, 700);
    } catch {
        showAlert('Tidak dapat mengunggah dokumen. Periksa koneksi lalu coba kembali.', 'uploadAlert');
    } finally {
        setSubmitting(form, false);
    }
};

const initializeDocumentUpload = () => {
    const session = requireUserSession();

    if (!session) return;

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    populateDocumentTypes(session.token);

    const form = document.getElementById('uploadDocumentForm');
    const fileInput = document.getElementById('documentFile');

    fileInput?.addEventListener('change', () => updateSelectedFile(fileInput.files[0]));
    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        submitDocumentUpload(form, session.token);
    });
};

const documentStatusLabels = {
    uploaded: 'Uploaded',
    analyzed: 'Analyzed',
    need_revision: 'Need revision',
    revised: 'Revised',
    ready: 'Ready',
    archived: 'Archived',
};

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const createTextElement = (tag, className, text) => {
    const element = document.createElement(tag);
    element.className = className;
    element.textContent = text;

    return element;
};

const createDocumentRow = (documentData) => {
    const row = document.createElement('tr');
    const documentCell = document.createElement('td');
    const title = createTextElement('strong', 'document-title', documentData.title);
    const topic = createTextElement('span', 'document-topic', documentData.topic || 'Tanpa topik');
    const typeCell = createTextElement('td', '', documentData.document_type?.label || '-');
    const statusCell = document.createElement('td');
    const status = createTextElement(
        'span',
        `status-badge status-${documentData.status}`,
        documentStatusLabels[documentData.status] || documentData.status,
    );
    const scoreCell = createTextElement('td', 'score-cell', documentData.latest_score ?? '-');
    const versionCell = createTextElement('td', '', `V${documentData.latest_version?.version_number || 1}`);
    const dateCell = createTextElement('td', '', formatDate(documentData.updated_at));
    const actionCell = document.createElement('td');
    const detailLink = createTextElement('a', 'detail-link', 'Detail');

    detailLink.href = `/documents/${documentData.id}`;
    documentCell.append(title, topic);
    statusCell.appendChild(status);
    actionCell.appendChild(detailLink);
    row.append(documentCell, typeCell, statusCell, scoreCell, versionCell, dateCell, actionCell);

    return row;
};

const populateLibraryTypeFilter = (documents) => {
    const filter = document.getElementById('documentTypeFilter');
    const types = new Map();

    documents.forEach((documentData) => {
        if (documentData.document_type) {
            types.set(documentData.document_type.name, documentData.document_type.label);
        }
    });

    [...types.entries()]
        .sort((first, second) => first[1].localeCompare(second[1], 'id'))
        .forEach(([name, label]) => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = label;
            filter.appendChild(option);
        });
};

const renderDocumentLibrary = (documents) => {
    const search = document.getElementById('documentSearch').value.trim().toLocaleLowerCase('id');
    const type = document.getElementById('documentTypeFilter').value;
    const status = document.getElementById('documentStatusFilter').value;
    const table = document.querySelector('.document-table-wrap');
    const body = document.getElementById('documentTableBody');
    const emptyState = document.getElementById('documentEmptyState');
    const emptyMessage = document.getElementById('documentEmptyMessage');
    const filteredDocuments = documents.filter((documentData) => {
        const searchableText = `${documentData.title} ${documentData.topic || ''}`.toLocaleLowerCase('id');
        const matchesSearch = searchableText.includes(search);
        const matchesType = !type || documentData.document_type?.name === type;
        const matchesStatus = !status || documentData.status === status;

        return matchesSearch && matchesType && matchesStatus;
    });

    body.replaceChildren(...filteredDocuments.map(createDocumentRow));
    document.getElementById('visibleDocumentCount').textContent = filteredDocuments.length;
    document.getElementById('totalDocumentCount').textContent = `${documents.length} total dokumen`;

    const hasResults = filteredDocuments.length > 0;
    table.classList.toggle('hidden', !hasResults);
    emptyState.classList.toggle('hidden', hasResults);
    emptyMessage.textContent = documents.length === 0
        ? 'Upload dokumen pertama untuk memulai analisis.'
        : 'Ubah kata pencarian atau reset filter untuk melihat dokumen lain.';
};

const loadDocumentLibrary = async (token) => {
    try {
        const response = await fetch('/api/documents', {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            throw new Error(data?.message);
        }

        const documents = data.data || [];
        const initialFilters = new URLSearchParams(window.location.search);

        populateLibraryTypeFilter(documents);
        if (initialFilters.get('search')) {
            document.getElementById('documentSearch').value = initialFilters.get('search');
        }
        if (initialFilters.get('type')) {
            document.getElementById('documentTypeFilter').value = initialFilters.get('type');
        }
        if (initialFilters.get('status')) {
            document.getElementById('documentStatusFilter').value = initialFilters.get('status');
        }
        renderDocumentLibrary(documents);
        document.getElementById('documentLoading').classList.add('hidden');
        document.getElementById('documentLibraryContent').classList.remove('hidden');

        ['documentSearch', 'documentTypeFilter', 'documentStatusFilter'].forEach((id) => {
            document.getElementById(id).addEventListener('input', () => renderDocumentLibrary(documents));
        });

        document.getElementById('clearDocumentFilters').addEventListener('click', () => {
            document.getElementById('documentSearch').value = '';
            document.getElementById('documentTypeFilter').value = '';
            document.getElementById('documentStatusFilter').value = '';
            renderDocumentLibrary(documents);
        });
    } catch {
        document.getElementById('documentLoading').classList.add('hidden');
        showAlert('Dokumen tidak dapat dimuat. Silakan muat ulang halaman.', 'libraryAlert');
    }
};

const initializeDocumentLibrary = () => {
    const session = requireUserSession();

    if (!session) return;

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    loadDocumentLibrary(session.token);
};

const formatFileSize = (bytes) => {
    if (!bytes) return '-';

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
};

const renderStringList = (id, items) => {
    const list = document.getElementById(id);
    const values = Array.isArray(items) && items.length ? items : ['Tidak ada catatan.'];

    list.replaceChildren(...values.map((value) => createTextElement('li', '', value)));
};

const createVersionRow = (version) => {
    const row = document.createElement('tr');

    row.append(
        createTextElement('td', 'score-cell', `V${version.version_number}`),
        createTextElement('td', '', version.file_original_name || '-'),
        createTextElement('td', 'uppercase-cell', version.file_type || '-'),
        createTextElement('td', '', formatFileSize(version.file_size)),
        createTextElement('td', '', version.revision_note || '-'),
        createTextElement('td', '', formatDate(version.uploaded_at || version.created_at)),
    );

    return row;
};

const createAspectRow = (aspect) => {
    const row = document.createElement('tr');

    row.append(
        createTextElement('td', 'document-title', aspect.aspect_name || '-'),
        createTextElement('td', 'score-cell', aspect.score ?? '-'),
        createTextElement('td', '', aspect.status || '-'),
        createTextElement('td', '', aspect.finding || '-'),
        createTextElement('td', '', aspect.recommendation || '-'),
    );

    return row;
};

const renderDocumentDetail = (documentData) => {
    document.getElementById('detailDocumentTitle').textContent = documentData.title || '-';
    document.getElementById('detailDocumentType').textContent = documentData.document_type?.label || 'Dokumen akademik';
    document.getElementById('detailDocumentDescription').textContent = documentData.description || 'Tidak ada deskripsi.';
    document.getElementById('detailDocumentTopic').textContent = documentData.topic || '-';
    document.getElementById('detailDocumentKeywords').textContent = documentData.keywords || '-';
    document.getElementById('detailVersionCount').textContent = documentData.versions?.length || 0;
    document.getElementById('detailUpdatedAt').textContent = formatDate(documentData.updated_at);
    document.getElementById('detailLatestScore').textContent = documentData.latest_score ?? '-';

    const status = document.getElementById('detailDocumentStatus');
    status.className = `status-badge status-${documentData.status}`;
    status.textContent = documentStatusLabels[documentData.status] || documentData.status;

    document.getElementById('uploadRevisionLink').href = `/documents/${documentData.id}/revisions/upload`;
    document.getElementById('comparisonLink').href = `/documents/${documentData.id}/comparison`;

    if (documentData.document_type?.name === 'article') {
        const reviewerLink = document.getElementById('reviewerMappingLink');
        reviewerLink.href = `/articles/${documentData.id}/reviewer-mapping`;
        reviewerLink.classList.remove('hidden');
    }

    document.getElementById('documentVersionTable').replaceChildren(
        ...(documentData.versions || []).map(createVersionRow),
    );
};

const isArticleDocument = (documentData) => {
    const typeName = (documentData?.document_type?.name || '').toLocaleLowerCase('id');
    const typeLabel = (documentData?.document_type?.label || '').toLocaleLowerCase('id');

    return typeName.includes('artikel')
        || typeName.includes('article')
        || typeLabel.includes('artikel')
        || typeLabel.includes('article');
};

const showJournalRecommendationError = (message) => {
    const box = document.getElementById('journalRecommendationError');

    if (!box) return;

    box.textContent = message;
    box.dataset.type = 'error';
    box.classList.remove('hidden');
};

const hideJournalRecommendationError = () => {
    const box = document.getElementById('journalRecommendationError');

    if (!box) return;

    box.textContent = '';
    box.classList.add('hidden');
};

const setJournalRecommendationLoading = (isLoading) => {
    const button = document.getElementById('generateJournalButton');
    const loading = document.getElementById('journalRecommendationLoading');

    if (button) {
        button.disabled = isLoading;
        button.querySelector('.button-label')?.classList.toggle('hidden', isLoading);
        button.querySelector('.button-loader')?.classList.toggle('hidden', !isLoading);
    }

    loading?.classList.toggle('hidden', !isLoading);
};

const createJournalMetaBadge = (text, className = '') => createTextElement(
    'span',
    `journal-recommendation-badge ${className}`.trim(),
    text || '-',
);

const createJournalActionLink = (url, label, className) => {
    const safeUrl = getSafeExternalUrl(url);

    if (!safeUrl) return null;

    const link = createTextElement('a', className, label);
    link.href = safeUrl;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';

    return link;
};

const createJournalInsight = (title, text, className) => {
    const item = document.createElement('div');
    item.className = `journal-recommendation-insight ${className}`;
    item.append(
        createTextElement('strong', '', title),
        createTextElement('p', '', text || '-'),
    );

    return item;
};

const createJournalRecommendationCard = (item, index) => {
    const journal = item.journal || {};
    const card = document.createElement('article');
    const header = document.createElement('div');
    const identity = document.createElement('div');
    const rank = createTextElement('span', 'journal-recommendation-rank', index + 1);
    const titleWrap = document.createElement('div');
    const badges = document.createElement('div');
    const actions = document.createElement('div');
    const insights = document.createElement('div');
    const websiteLink = createJournalActionLink(journal.website_url, 'Website', 'detail-link journal-recommendation-link');
    const templateLink = createJournalActionLink(journal.template_url, 'Template', 'secondary-button button-link journal-recommendation-link');

    card.className = 'journal-recommendation-card';
    header.className = 'journal-recommendation-header';
    identity.className = 'journal-recommendation-identity';
    badges.className = 'journal-recommendation-badges';
    actions.className = 'journal-recommendation-actions';
    insights.className = 'journal-recommendation-insights';

    titleWrap.append(
        createTextElement('h3', '', journal.name || '-'),
        createTextElement('p', '', journal.publisher || 'Publisher belum tersedia'),
    );
    identity.append(rank, titleWrap);
    badges.append(
        createJournalMetaBadge(journal.sinta_level || '-'),
        createJournalMetaBadge(journal.subject_area || 'Subject belum tersedia'),
        createJournalMetaBadge(`Fit Score: ${item.fit_score ?? 0}/100`, 'journal-recommendation-score'),
    );
    actions.append(...[websiteLink, templateLink].filter(Boolean));
    header.append(identity, actions);
    insights.append(
        createJournalInsight('Alasan Cocok', item.fit_reason, 'journal-recommendation-fit'),
        createJournalInsight('Risiko Submit', item.submission_risk, 'journal-recommendation-risk'),
        createJournalInsight('Saran Perbaikan', item.suggested_improvement, 'journal-recommendation-improvement'),
    );
    card.append(header, badges, insights);

    return card;
};

const renderJournalRecommendations = (recommendations) => {
    const empty = document.getElementById('journalRecommendationEmpty');
    const list = document.getElementById('journalRecommendationList');
    const items = recommendations || [];

    if (!empty || !list) return;

    if (items.length === 0) {
        empty.classList.remove('hidden');
        list.classList.add('hidden');
        list.replaceChildren();
        return;
    }

    empty.classList.add('hidden');
    list.classList.remove('hidden');
    list.replaceChildren(...items.map(createJournalRecommendationCard));
};

const loadJournalRecommendations = async (documentId, token) => {
    hideJournalRecommendationError();

    try {
        const response = await fetch(`/api/documents/${documentId}/journal-recommendations`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            showJournalRecommendationError(data.message || 'Rekomendasi jurnal tidak dapat dimuat.');
            return;
        }

        renderJournalRecommendations(data.data || []);
    } catch {
        showJournalRecommendationError('Rekomendasi jurnal tidak dapat dimuat. Silakan coba kembali.');
    }
};

const generateJournalRecommendations = async (documentId, token) => {
    hideJournalRecommendationError();
    setJournalRecommendationLoading(true);

    try {
        const response = await fetch(`/api/documents/${documentId}/journal-recommendations`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            showJournalRecommendationError(data.message || 'Rekomendasi jurnal gagal dibuat.');
            return;
        }

        renderJournalRecommendations(data.data || []);
        showAlert('Rekomendasi jurnal berhasil dibuat.', 'detailAlert', 'success');
    } catch {
        showJournalRecommendationError('Rekomendasi jurnal tidak dapat dibuat. Silakan coba kembali.');
    } finally {
        setJournalRecommendationLoading(false);
    }
};

const renderLatestAnalysis = (analysis) => {
    document.getElementById('analysisEmptyState').classList.add('hidden');
    document.getElementById('analysisDetailContent').classList.remove('hidden');
    document.getElementById('analysisTotalScore').textContent = analysis.total_score ?? '-';
    document.getElementById('analysisStatus').textContent = analysis.status || '-';
    document.getElementById('analysisCreatedAt').textContent = formatDate(analysis.created_at);
    document.getElementById('analysisSummary').textContent = analysis.summary || '-';

    const versionBadge = document.getElementById('analysisVersionBadge');
    versionBadge.textContent = `Versi ${analysis.document_version?.version_number || '-'}`;
    versionBadge.classList.remove('hidden');

    renderStringList('analysisMainIssues', analysis.main_issues);
    renderStringList('analysisRecommendations', analysis.recommendations);
    renderStringList('analysisPriorities', analysis.revision_priorities);
    document.getElementById('analysisAspectTable').replaceChildren(
        ...(analysis.aspect_scores || []).map(createAspectRow),
    );
};

const loadLatestAnalysis = async (documentId, token) => {
    const response = await fetch(`/api/documents/${documentId}/analysis`, {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });

    if (response.status === 404) return;

    const data = await response.json();

    if (response.status === 401) {
        storage.clearSession();
        window.location.href = '/login';
        return;
    }

    if (response.ok) {
        renderLatestAnalysis(data.data);
    }
};

const setAnalyzing = (isAnalyzing) => {
    const button = document.getElementById('analyzeDocumentButton');
    button.disabled = isAnalyzing;
    button.querySelector('.button-label').classList.toggle('hidden', isAnalyzing);
    button.querySelector('.button-loader').classList.toggle('hidden', !isAnalyzing);
};

const analyzeDocument = async (documentId, token) => {
    hideAlert('detailAlert');
    setAnalyzing(true);

    try {
        const response = await fetch(`/api/documents/${documentId}/analyze`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            showAlert(data.message || 'Analisis AI gagal.', 'detailAlert');
            return;
        }

        renderLatestAnalysis(data.data);
        document.getElementById('detailLatestScore').textContent = data.data.total_score ?? '-';
        showAlert('Analisis AI berhasil dan hasil terbaru sudah ditampilkan.', 'detailAlert', 'success');
    } catch {
        showAlert('Analisis AI tidak dapat dijalankan. Silakan coba kembali.', 'detailAlert');
    } finally {
        setAnalyzing(false);
    }
};

const loadDocumentDetail = async (documentId, token) => {
    try {
        const response = await fetch(`/api/documents/${documentId}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            throw new Error(data.message || 'Detail dokumen tidak dapat dimuat.');
        }

        renderDocumentDetail(data.data);
        if (isArticleDocument(data.data)) {
            document.getElementById('journalRecommendationSection')?.classList.remove('hidden');
            await loadJournalRecommendations(documentId, token);
        } else {
            document.getElementById('journalRecommendationSection')?.classList.add('hidden');
        }
        await loadLatestAnalysis(documentId, token);
        document.getElementById('documentDetailLoading').classList.add('hidden');
        document.getElementById('documentDetailContent').classList.remove('hidden');
    } catch (error) {
        document.getElementById('documentDetailLoading').classList.add('hidden');
        showAlert(error.message || 'Detail dokumen tidak dapat dimuat.', 'detailAlert');
    }
};

const initializeDocumentDetail = () => {
    const session = requireUserSession();

    if (!session) return;

    const documentId = document.querySelector('[data-document-id]')?.dataset.documentId;

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    document.getElementById('analyzeDocumentButton')?.addEventListener('click', () => {
        analyzeDocument(documentId, session.token);
    });
    document.getElementById('generateJournalButton')?.addEventListener('click', () => {
        generateJournalRecommendations(documentId, session.token);
    });
    loadDocumentDetail(documentId, session.token);
};

const renderRevisionDocumentInfo = (documentData) => {
    const info = document.getElementById('revisionDocumentInfo');
    const currentVersion = documentData.latest_version?.version_number || documentData.versions?.length || 1;
    const nextVersion = currentVersion + 1;
    const title = createTextElement('strong', 'revision-context-title', documentData.title || '-');
    const type = createTextElement('span', '', documentData.document_type?.label || 'Dokumen akademik');
    const status = createTextElement(
        'span',
        `status-badge status-${documentData.status}`,
        documentStatusLabels[documentData.status] || documentData.status,
    );
    const version = createTextElement('p', '', `Versi saat ini V${currentVersion}. File baru akan disimpan sebagai V${nextVersion}.`);

    info.replaceChildren(title, type, status, version);
    info.classList.add('is-loaded');
};

const updateRevisionFile = (file) => {
    const label = document.getElementById('revisionFileLabel');
    const meta = document.getElementById('revisionFileMeta');
    const area = document.getElementById('revisionFileDropArea');

    if (!file) {
        label.textContent = 'Pilih file revisi PDF atau DOCX';
        meta.textContent = 'Ukuran maksimal 10 MB';
        area.classList.remove('has-file');
        return;
    }

    label.textContent = file.name;
    meta.textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;
    area.classList.add('has-file');
};

const loadRevisionDocumentInfo = async (documentId, token) => {
    try {
        const response = await fetch(`/api/documents/${documentId}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            throw new Error(data.message || 'Informasi dokumen tidak dapat dimuat.');
        }

        renderRevisionDocumentInfo(data.data);
    } catch (error) {
        showAlert(error.message || 'Informasi dokumen tidak dapat dimuat.', 'revisionAlert');
    }
};

const submitDocumentRevision = async (form, documentId, token) => {
    hideAlert('revisionAlert');

    if (!form.reportValidity()) return;

    const fileError = validateDocumentFile(form.elements.file.files[0]);

    if (fileError) {
        showAlert(fileError, 'revisionAlert');
        return;
    }

    setSubmitting(form, true);

    try {
        const response = await fetch(`/api/documents/${documentId}/versions`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
            body: new FormData(form),
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Upload revisi gagal.'), 'revisionAlert');
            return;
        }

        showAlert(
            `Revisi berhasil disimpan sebagai V${data.data?.version?.version_number}. Membuka detail dokumen...`,
            'revisionAlert',
            'success',
        );
        window.setTimeout(() => {
            window.location.href = `/documents/${documentId}`;
        }, 700);
    } catch {
        showAlert('Revisi tidak dapat diunggah. Periksa koneksi lalu coba kembali.', 'revisionAlert');
    } finally {
        setSubmitting(form, false);
    }
};

const initializeDocumentRevisionUpload = () => {
    const session = requireUserSession();

    if (!session) return;

    const documentId = document.querySelector('[data-document-id]')?.dataset.documentId;
    const form = document.getElementById('uploadRevisionForm');
    const fileInput = document.getElementById('revisionFile');

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    loadRevisionDocumentInfo(documentId, session.token);
    fileInput?.addEventListener('change', () => updateRevisionFile(fileInput.files[0]));
    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        submitDocumentRevision(form, documentId, session.token);
    });
};

const comparisonStatusLabels = {
    improved: 'Meningkat',
    declined: 'Menurun',
    unchanged: 'Tetap',
};

const formatDifference = (difference) => {
    const value = Number(difference) || 0;
    return value > 0 ? `+${value}` : `${value}`;
};

const setComparing = (isComparing) => {
    const button = document.getElementById('compareVersionsButton');

    button.disabled = isComparing;
    button.querySelector('.button-label').classList.toggle('hidden', isComparing);
    button.querySelector('.button-loader').classList.toggle('hidden', !isComparing);
};

const createComparisonStatus = (status) => createTextElement(
    'span',
    `comparison-status comparison-status-${status}`,
    comparisonStatusLabels[status] || 'Tetap',
);

const createComparisonRow = (aspect) => {
    const row = document.createElement('tr');
    const differenceCell = document.createElement('td');
    const statusCell = document.createElement('td');

    differenceCell.appendChild(createTextElement(
        'strong',
        `comparison-difference comparison-difference-${aspect.status}`,
        formatDifference(aspect.difference),
    ));
    statusCell.appendChild(createComparisonStatus(aspect.status));
    row.append(
        createTextElement('td', 'document-title', aspect.aspect_name || '-'),
        createTextElement('td', 'score-cell', aspect.from_score ?? '-'),
        createTextElement('td', 'score-cell', aspect.to_score ?? '-'),
        differenceCell,
        statusCell,
    );

    return row;
};

const getSelectedVersionLabel = (selectId) => {
    const select = document.getElementById(selectId);
    return select.options[select.selectedIndex]?.textContent || '-';
};

const renderComparisonResult = (comparison) => {
    document.getElementById('comparisonEmptyState').classList.add('hidden');
    document.getElementById('comparisonResult').classList.remove('hidden');
    document.getElementById('fromVersionLabel').textContent = getSelectedVersionLabel('fromVersion');
    document.getElementById('toVersionLabel').textContent = getSelectedVersionLabel('toVersion');
    document.getElementById('fromTotalScore').textContent = comparison.from_total_score ?? '-';
    document.getElementById('toTotalScore').textContent = comparison.to_total_score ?? '-';
    document.getElementById('totalDifference').textContent = formatDifference(comparison.total_difference);
    document.getElementById('totalStatus').textContent = comparisonStatusLabels[comparison.total_status] || 'Tetap';

    const differenceCard = document.getElementById('totalDifferenceCard');
    differenceCard.className = `comparison-score-card comparison-score-${comparison.total_status}`;

    const aspects = comparison.aspect_comparison || [];
    const body = document.getElementById('comparisonTableBody');

    if (aspects.length === 0) {
        const row = document.createElement('tr');
        const cell = createTextElement('td', 'comparison-no-data', 'Tidak ada skor aspek yang dapat dibandingkan.');
        cell.colSpan = 5;
        row.appendChild(cell);
        body.replaceChildren(row);
        return;
    }

    body.replaceChildren(...aspects.map(createComparisonRow));
};

const compareDocumentVersions = async (documentId, token) => {
    const fromVersionId = document.getElementById('fromVersion').value;
    const toVersionId = document.getElementById('toVersion').value;

    hideAlert('comparisonAlert');

    if (!fromVersionId || !toVersionId) {
        showAlert('Pilih versi awal dan versi revisi terlebih dahulu.', 'comparisonAlert');
        return;
    }

    if (fromVersionId === toVersionId) {
        showAlert('Versi awal dan versi revisi tidak boleh sama.', 'comparisonAlert');
        return;
    }

    setComparing(true);

    try {
        const query = new URLSearchParams({
            from_version_id: fromVersionId,
            to_version_id: toVersionId,
        });
        const response = await fetch(`/api/documents/${documentId}/comparison?${query}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Perbandingan versi gagal.'), 'comparisonAlert');
            return;
        }

        renderComparisonResult(data.data);
    } catch {
        showAlert('Perbandingan versi tidak dapat dimuat. Silakan coba kembali.', 'comparisonAlert');
    } finally {
        setComparing(false);
    }
};

const createVersionOption = (version) => {
    const option = document.createElement('option');
    option.value = version.id;
    option.textContent = `V${version.version_number} - ${version.file_original_name || 'Tanpa nama file'}`;

    return option;
};

const renderComparisonDocument = (documentData) => {
    const versions = [...(documentData.versions || [])].sort(
        (first, second) => first.version_number - second.version_number,
    );
    const fromSelect = document.getElementById('fromVersion');
    const toSelect = document.getElementById('toVersion');
    const button = document.getElementById('compareVersionsButton');

    document.getElementById('comparisonDocumentTitle').textContent = documentData.title || '-';
    document.getElementById('comparisonVersionCount').textContent = `${versions.length} versi`;
    fromSelect.replaceChildren(
        createTextElement('option', '', 'Pilih versi awal'),
        ...versions.map(createVersionOption),
    );
    toSelect.replaceChildren(
        createTextElement('option', '', 'Pilih versi revisi'),
        ...versions.map(createVersionOption),
    );
    fromSelect.options[0].value = '';
    toSelect.options[0].value = '';

    if (versions.length >= 2) {
        fromSelect.value = `${versions[0].id}`;
        toSelect.value = `${versions[versions.length - 1].id}`;
        button.disabled = false;
        return;
    }

    button.disabled = true;
    showAlert(
        'Dokumen memerlukan minimal dua versi sebelum dapat dibandingkan.',
        'comparisonAlert',
    );
};

const loadComparisonDocument = async (documentId, token) => {
    try {
        const response = await fetch(`/api/documents/${documentId}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            throw new Error(data.message || 'Dokumen tidak dapat dimuat.');
        }

        renderComparisonDocument(data.data);
        document.getElementById('comparisonLoading').classList.add('hidden');
        document.getElementById('comparisonContent').classList.remove('hidden');
    } catch (error) {
        document.getElementById('comparisonLoading').classList.add('hidden');
        showAlert(error.message || 'Dokumen tidak dapat dimuat.', 'comparisonAlert');
    }
};

const initializeDocumentComparison = () => {
    const session = requireUserSession();

    if (!session) return;

    const documentId = document.querySelector('[data-document-id]')?.dataset.documentId;

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    document.getElementById('compareVersionsButton')?.addEventListener('click', () => {
        compareDocumentVersions(documentId, session.token);
    });
    loadComparisonDocument(documentId, session.token);
};

let reviewerComments = [];
let reviewerDocumentVersions = [];

const reviewerPriorityLabels = {
    minor: 'Minor',
    major: 'Major',
    critical: 'Critical',
};

const reviewerStatusLabels = {
    pending: 'Pending',
    in_progress: 'In progress',
    done: 'Selesai',
    rejected_with_reason: 'Ditolak dengan alasan',
};

const setButtonLoading = (button, isLoading) => {
    button.disabled = isLoading;
    button.querySelector('.button-label')?.classList.toggle('hidden', isLoading);
    button.querySelector('.button-loader')?.classList.toggle('hidden', !isLoading);
};

const createReviewerBadge = (type, value, label) => createTextElement(
    'span',
    `reviewer-badge reviewer-${type}-${value}`,
    label,
);

const createReviewerCommentCard = (comment) => {
    const card = document.createElement('article');
    const header = document.createElement('div');
    const identity = document.createElement('div');
    const badges = document.createElement('div');
    const actions = document.createElement('div');
    const responseButton = createTextElement('button', 'primary-button', comment.response ? 'Edit respons' : 'Buat respons');
    const section = comment.related_section || 'Bagian tidak ditentukan';
    const commentNumber = comment.comment_number ? `Komentar ${comment.comment_number}` : 'Tanpa nomor';

    card.className = 'reviewer-comment-item';
    header.className = 'reviewer-comment-header';
    identity.append(
        createTextElement('strong', '', comment.reviewer_label || 'Reviewer'),
        createTextElement('span', '', `${commentNumber} | ${section}`),
    );
    badges.className = 'reviewer-comment-badges';
    badges.append(
        createReviewerBadge('priority', comment.priority, reviewerPriorityLabels[comment.priority] || comment.priority),
        createReviewerBadge('status', comment.status, reviewerStatusLabels[comment.status] || comment.status),
    );
    header.append(identity, badges);
    responseButton.type = 'button';
    responseButton.dataset.commentId = comment.id;
    responseButton.addEventListener('click', () => openAuthorResponseEditor(comment.id));
    actions.className = 'reviewer-comment-actions';
    actions.appendChild(responseButton);
    card.append(
        header,
        createTextElement('p', 'reviewer-comment-text', comment.original_comment || '-'),
        actions,
    );

    return card;
};

const renderReviewerComments = () => {
    const list = document.getElementById('reviewerCommentList');
    document.getElementById('reviewerCommentCount').textContent = `${reviewerComments.length} komentar`;

    if (reviewerComments.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'inline-empty reviewer-list-empty';
        empty.append(
            createTextElement('strong', '', 'Belum ada komentar reviewer.'),
            createTextElement('p', '', 'Paste catatan reviewer untuk diproses AI atau tambahkan komentar secara manual.'),
        );
        list.replaceChildren(empty);
        return;
    }

    list.replaceChildren(...reviewerComments.map(createReviewerCommentCard));
};

const loadReviewerComments = async (documentId, token) => {
    const response = await fetch(`/api/articles/${documentId}/reviewer-comments`, {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || 'Komentar reviewer tidak dapat dimuat.');
    }

    reviewerComments = data.data || [];
    renderReviewerComments();
};

const createMatrixRow = (item) => {
    const row = document.createElement('tr');
    const version = item.revised_version_number ? `V${item.revised_version_number}` : null;
    const location = [item.revision_location, version].filter(Boolean).join(' | ') || '-';
    const statusCell = document.createElement('td');

    statusCell.appendChild(createReviewerBadge(
        'status',
        item.status,
        reviewerStatusLabels[item.status] || item.status,
    ));
    row.append(
        createTextElement('td', '', item.reviewer || '-'),
        createTextElement('td', '', item.original_comment || '-'),
        createTextElement('td', '', item.author_response || '-'),
        createTextElement('td', '', item.revision_made || '-'),
        createTextElement('td', '', location),
        statusCell,
    );

    return row;
};

const renderResponseMatrix = (items) => {
    const body = document.getElementById('responseMatrixTableBody');

    if (items.length === 0) {
        const row = document.createElement('tr');
        const cell = createTextElement('td', 'comparison-no-data', 'Response matrix belum tersedia.');
        cell.colSpan = 6;
        row.appendChild(cell);
        body.replaceChildren(row);
        return;
    }

    body.replaceChildren(...items.map(createMatrixRow));
};

const loadResponseMatrix = async (documentId, token) => {
    const response = await fetch(`/api/articles/${documentId}/response-matrix`, {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || 'Response matrix tidak dapat dimuat.');
    }

    renderResponseMatrix(data.data?.response_matrix || []);
};

const refreshReviewerWorkspace = async (documentId, token) => {
    await Promise.all([
        loadReviewerComments(documentId, token),
        loadResponseMatrix(documentId, token),
    ]);
};

const parseReviewerComments = async (documentId, token) => {
    const reviewerText = document.getElementById('reviewerText');
    const button = document.getElementById('parseReviewerButton');

    hideAlert('reviewerAlert');

    if (!reviewerText.value.trim()) {
        showAlert('Catatan reviewer wajib diisi sebelum diproses.', 'reviewerAlert');
        return;
    }

    setButtonLoading(button, true);

    try {
        const response = await fetch(`/api/articles/${documentId}/reviewer-comments/parse`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reviewer_text: reviewerText.value,
                save_to_database: true,
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Komentar reviewer gagal diproses.'), 'reviewerAlert');
            return;
        }

        reviewerText.value = '';
        await refreshReviewerWorkspace(documentId, token);
        showAlert('Komentar reviewer berhasil diproses dan disimpan.', 'reviewerAlert', 'success');
    } catch {
        showAlert('Komentar reviewer tidak dapat diproses. Silakan coba kembali.', 'reviewerAlert');
    } finally {
        setButtonLoading(button, false);
    }
};

const submitManualReviewerComment = async (form, documentId, token) => {
    hideAlert('reviewerAlert');

    if (!form.reportValidity()) return;

    setSubmitting(form, true);

    try {
        const values = Object.fromEntries(new FormData(form).entries());
        const response = await fetch(`/api/articles/${documentId}/reviewer-comments`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ...values,
                comment_number: values.comment_number || null,
                related_section: values.related_section || null,
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Komentar manual gagal disimpan.'), 'reviewerAlert');
            return;
        }

        form.elements.original_comment.value = '';
        form.elements.comment_number.value = '';
        await refreshReviewerWorkspace(documentId, token);
        showAlert('Komentar manual berhasil disimpan.', 'reviewerAlert', 'success');
    } catch {
        showAlert('Komentar manual tidak dapat disimpan. Silakan coba kembali.', 'reviewerAlert');
    } finally {
        setSubmitting(form, false);
    }
};

const populateRevisedVersionOptions = () => {
    const select = document.getElementById('revisedVersionId');
    const emptyOption = createTextElement('option', '', 'Tidak ditentukan');
    emptyOption.value = '';
    select.replaceChildren(emptyOption, ...reviewerDocumentVersions.map(createVersionOption));
};

const openAuthorResponseEditor = (commentId) => {
    const comment = reviewerComments.find((item) => `${item.id}` === `${commentId}`);

    if (!comment) {
        showAlert('Komentar reviewer tidak ditemukan.', 'reviewerAlert');
        return;
    }

    const response = comment.response || {};
    const editor = document.getElementById('responseEditorCard');

    document.getElementById('selectedReviewerCommentId').value = comment.id;
    document.getElementById('selectedReviewerComment').textContent = comment.original_comment || '-';
    document.getElementById('revisionMade').value = response.revision_made || '';
    document.getElementById('revisionLocation').value = response.revision_location || '';
    document.getElementById('revisedVersionId').value = response.revised_version_id || '';
    document.getElementById('authorResponse').value = response.author_response || '';
    editor.classList.remove('hidden');
    editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const generateAuthorResponse = async (token) => {
    const commentId = document.getElementById('selectedReviewerCommentId').value;
    const revisionMade = document.getElementById('revisionMade').value.trim();
    const revisionLocation = document.getElementById('revisionLocation').value.trim();
    const button = document.getElementById('generateAuthorResponseButton');

    hideAlert('reviewerAlert');

    if (!commentId || !revisionMade) {
        showAlert('Isi perubahan yang dilakukan sebelum membuat draft respons.', 'reviewerAlert');
        return;
    }

    setButtonLoading(button, true);

    try {
        const response = await fetch(`/api/reviewer-comments/${commentId}/generate-response`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                revision_made: revisionMade,
                revision_location: revisionLocation || null,
                save_to_database: false,
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Draft respons gagal dibuat.'), 'reviewerAlert');
            return;
        }

        document.getElementById('authorResponse').value = data.data?.generated_response?.author_response || '';
        showAlert('Draft respons AI berhasil dibuat. Periksa kembali sebelum disimpan.', 'reviewerAlert', 'success');
    } catch {
        showAlert('Draft respons tidak dapat dibuat. Silakan coba kembali.', 'reviewerAlert');
    } finally {
        setButtonLoading(button, false);
    }
};

const submitAuthorResponse = async (form, documentId, token) => {
    hideAlert('reviewerAlert');

    if (!form.reportValidity()) return;

    const commentId = document.getElementById('selectedReviewerCommentId').value;
    setSubmitting(form, true);

    try {
        const response = await fetch(`/api/reviewer-comments/${commentId}/responses`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                author_response: document.getElementById('authorResponse').value,
                revision_made: document.getElementById('revisionMade').value || null,
                revision_location: document.getElementById('revisionLocation').value || null,
                revised_version_id: document.getElementById('revisedVersionId').value || null,
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            showAlert(getErrorMessage(data, 'Respons penulis gagal disimpan.'), 'reviewerAlert');
            return;
        }

        await refreshReviewerWorkspace(documentId, token);
        document.getElementById('responseEditorCard').classList.add('hidden');
        showAlert('Respons penulis berhasil disimpan.', 'reviewerAlert', 'success');
    } catch {
        showAlert('Respons penulis tidak dapat disimpan. Silakan coba kembali.', 'reviewerAlert');
    } finally {
        setSubmitting(form, false);
    }
};

const downloadResponseLetter = async (documentId, token) => {
    const button = document.getElementById('downloadResponseLetterButton');
    hideAlert('reviewerAlert');
    setButtonLoading(button, true);

    try {
        const response = await fetch(`/api/articles/${documentId}/response-letter`, {
            headers: {
                Accept: 'application/pdf',
                Authorization: `Bearer ${token}`,
            },
        });

        if (!response.ok) {
            const data = await response.json();
            showAlert(data.message || 'Response Letter gagal dibuat.', 'reviewerAlert');
            return;
        }

        const disposition = response.headers.get('content-disposition') || '';
        const fileName = disposition.match(/filename="?([^";]+)"?/i)?.[1]
            || `response_to_reviewers_document_${documentId}.pdf`;
        const url = URL.createObjectURL(await response.blob());
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        showAlert('Response Letter berhasil diunduh.', 'reviewerAlert', 'success');
    } catch {
        showAlert('Response Letter tidak dapat diunduh. Silakan coba kembali.', 'reviewerAlert');
    } finally {
        setButtonLoading(button, false);
    }
};

const loadReviewerWorkspace = async (documentId, token) => {
    try {
        const response = await fetch(`/api/documents/${documentId}`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const data = await response.json();

        if (response.status === 401) {
            storage.clearSession();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            throw new Error(data.message || 'Artikel tidak dapat dimuat.');
        }

        if (data.data?.document_type?.name !== 'article') {
            throw new Error('Reviewer Mapping hanya tersedia untuk artikel ilmiah.');
        }

        reviewerDocumentVersions = [...(data.data.versions || [])].sort(
            (first, second) => first.version_number - second.version_number,
        );
        document.getElementById('reviewerArticleTitle').textContent = data.data.title || '-';
        populateRevisedVersionOptions();
        await refreshReviewerWorkspace(documentId, token);
        document.getElementById('downloadResponseLetterButton').disabled = false;
        document.getElementById('reviewerLoading').classList.add('hidden');
        document.getElementById('reviewerContent').classList.remove('hidden');
    } catch (error) {
        document.getElementById('reviewerLoading').classList.add('hidden');
        showAlert(error.message || 'Reviewer workspace tidak dapat dimuat.', 'reviewerAlert');
    }
};

const initializeReviewerMapping = () => {
    const session = requireUserSession();

    if (!session) return;

    const documentId = document.querySelector('[data-document-id]')?.dataset.documentId;
    const manualForm = document.getElementById('manualCommentForm');
    const responseForm = document.getElementById('authorResponseForm');

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
    document.getElementById('toggleManualCommentButton')?.addEventListener('click', () => {
        document.getElementById('manualCommentCard').classList.toggle('hidden');
    });
    document.getElementById('parseReviewerButton')?.addEventListener('click', () => {
        parseReviewerComments(documentId, session.token);
    });
    document.getElementById('generateAuthorResponseButton')?.addEventListener('click', () => {
        generateAuthorResponse(session.token);
    });
    document.getElementById('closeResponseEditorButton')?.addEventListener('click', () => {
        document.getElementById('responseEditorCard').classList.add('hidden');
    });
    document.getElementById('downloadResponseLetterButton')?.addEventListener('click', () => {
        downloadResponseLetter(documentId, session.token);
    });
    manualForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        submitManualReviewerComment(manualForm, documentId, session.token);
    });
    responseForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        submitAuthorResponse(responseForm, documentId, session.token);
    });
    loadReviewerWorkspace(documentId, session.token);
};

const initializeUserDestination = () => {
    if (!requireUserSession()) return;

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
};

document.addEventListener('DOMContentLoaded', () => {
    const page = document.body.dataset.page;

    if (page === 'login' || page === 'register') {
        initializeAuthPage(page);
    }

    if (page === 'user-dashboard') {
        initializeUserDashboard();
    }

    if (page === 'admin-dashboard') {
        initializeAdminDashboard();
    }

    if (page === 'admin-users') {
        initializeAdminUsers();
    }

    if (page === 'admin-documents') {
        initializeAdminDocuments();
    }

    if (page === 'admin-rubrics') {
        initializeAdminRubrics();
    }

    if (page === 'admin-journals') {
        initializeAdminJournals();
    }

    if (page === 'document-upload') {
        initializeDocumentUpload();
    }

    if (page === 'document-library') {
        initializeDocumentLibrary();
    }

    if (page === 'document-detail') {
        initializeDocumentDetail();
    }

    if (page === 'document-revision-upload') {
        initializeDocumentRevisionUpload();
    }

    if (page === 'document-comparison') {
        initializeDocumentComparison();
    }

    if (page === 'reviewer-mapping') {
        initializeReviewerMapping();
    }

    if (page === 'document-action-placeholder') {
        initializeUserDestination();
    }
});
