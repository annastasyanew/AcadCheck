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

        populateLibraryTypeFilter(documents);
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

const initializeUserDestination = () => {
    if (!requireUserSession()) return;

    document.querySelector('[data-logout]')?.addEventListener('click', logout);
};

document.addEventListener('DOMContentLoaded', () => {
    const page = document.body.dataset.page;

    if (page === 'login' || page === 'register') {
        initializeAuthPage(page);
    }

    if (page === 'user-dashboard' || page === 'admin-dashboard') {
        initializeDashboardPlaceholder(page);
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

    if (page === 'document-action-placeholder') {
        initializeUserDestination();
    }
});
