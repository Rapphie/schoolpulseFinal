<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SchoolPulse')</title>
    @yield('head')
    @include('components.head')
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    @livewireStyles

    @stack('styles')
</head>

<body class="bg-light min-vh-100">
    @include('components.topbar')

    <div id="content" class="w-100">
        @include('components.sidebar')
        <div class="sidebar-overlay"></div>

        <main>
            {{-- Toast notification container (fixed, top-right) --}}
            <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show session-alert" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center session-alert"
                    role="alert">
                    <i data-feather="alert-circle" class="icon-sm me-2"></i>
                    <span>{{ session('warning') }}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center session-alert"
                    role="alert" style="border-left: 4px solid #dc3545; font-weight: 500;">
                    <i data-feather="alert-triangle" class="icon-sm me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show session-alert" role="alert"
                    style="border-left: 4px solid #dc3545; font-weight: 500;">
                    <div class="d-flex align-items-start">
                        <i data-feather="alert-triangle" class="icon-sm me-2 mt-1"></i>
                        <div>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        @include('components.modals.logout-modal')
    </div>
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/angular.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('css/feather.min.js') }}"></script>

    <script>
        // Function to update Philippine time and date
        function updatePhilippineTime() {
            const options = {
                timeZone: 'Asia/Manila',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const dateOptions = {
                timeZone: 'Asia/Manila',
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-PH', options);
            const dateString = now.toLocaleDateString('en-PH', dateOptions);
            const timeElement = document.getElementById('ph-time');
            if (timeElement) {
                timeElement.innerHTML =
                    '<div class="text-end">' + timeString + '</div>' +
                    '<div class="small">' + dateString + ' (PHT)</div>';
            }
        }

        // Update time immediately and then every second
        updatePhilippineTime();
        setInterval(updatePhilippineTime, 1000);
    </script>

    <script>
        // Initialize Feather Icons and simplify sidebar toggle behavior
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined' && typeof feather.replace === 'function') {
                feather.replace();
            }

            const toggleBtn = document.getElementById('toggleBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
            const content = document.getElementById('content');
            const topBar = document.querySelector('.top-bar');
            const overlay = document.querySelector('.sidebar-overlay');

            const isMobile = () => window.innerWidth < 768;

            const openMobile = () => {
                if (!sidebar) return;
                sidebar.classList.add('show');
                sidebar.classList.remove('collapsed');
                document.body.style.overflow = 'hidden';
            };

            const closeMobile = () => {
                if (!sidebar) return;
                sidebar.classList.remove('show');
                sidebar.classList.add('collapsed');
                document.body.style.overflow = '';
            };

            // Initial state for mobile
            if (sidebar && isMobile()) {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('show');
            }

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    if (isMobile()) {
                        sidebar.classList.contains('show') ? closeMobile() : openMobile();
                        return;
                    }

                    const collapsed = sidebar.classList.toggle('collapsed');
                    if (collapsed) sidebar.classList.remove('show');
                    else sidebar.classList.add('show');
                    if (content) content.classList.toggle('content-shifted');
                    if (topBar) topBar.classList.toggle('content-shifted');
                });
            }

            if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeMobile);
            if (overlay) overlay.addEventListener('click', closeMobile);

            document.addEventListener('click', function(event) {
                if (!sidebar) return;
                const clickInside = sidebar.contains(event.target);
                const clickToggle = toggleBtn && toggleBtn.contains(event.target);
                const clickOverlay = overlay && overlay.contains(event.target);
                if (!clickInside && !clickToggle && !clickOverlay && isMobile() && sidebar.classList
                    .contains('show')) {
                    closeMobile();
                }
            });

            const reinitFeather = () => {
                if (typeof feather !== 'undefined' && typeof feather.replace === 'function') {
                    feather.replace();
                }
            };
            document.querySelectorAll('.dropdown').forEach(d => d.addEventListener('shown.bs.dropdown',
                reinitFeather));
            document.querySelectorAll('.modal').forEach(m => m.addEventListener('shown.bs.modal', reinitFeather));
            document.querySelectorAll('.sidebar .collapse').forEach(s => s.addEventListener('shown.bs.collapse',
                reinitFeather));

            // Auto-open active submenu on page load
            document.querySelectorAll('.sidebar .collapse.show').forEach(menu => {
                const toggler = document.querySelector('[href="#' + menu.id + '"]');
                if (toggler) toggler.setAttribute('aria-expanded', 'true');
            });

            window.addEventListener('resize', function() {
                if (isMobile()) {
                    if (topBar) topBar.classList.remove('content-shifted');
                    if (content) content.classList.remove('content-shifted');
                    if (sidebar && !sidebar.classList.contains('show')) sidebar.classList.add('collapsed');
                } else {
                    if (sidebar && sidebar.classList.contains('collapsed')) {
                        if (topBar) topBar.classList.add('content-shifted');
                        if (content) content.classList.add('content-shifted');
                    } else {
                        if (topBar) topBar.classList.remove('content-shifted');
                        if (content) content.classList.remove('content-shifted');
                    }
                    document.body.style.overflow = '';
                }
            });
        });
    </script>

    @livewireScripts

    @stack('scripts')

    <script>
        /**
         * Global toast notification helper.
         * Usage: showToast('Score saved!', 'success');
         *        showToast('Something went wrong.', 'danger');
         */
        window.showToast = function(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const icons = {
                success: 'check-circle',
                danger: 'alert-triangle',
                warning: 'alert-circle',
                info: 'info',
            };
            const bgColors = {
                success: '#198754',
                danger: '#dc3545',
                warning: '#ffc107',
                info: '#0d6efd',
            };

            const toastId = 'toast-' + Date.now();
            const iconName = icons[type] || icons.info;
            const bgColor = bgColors[type] || bgColors.info;
            const textColor = type === 'warning' ? '#000' : '#fff';

            const toastHTML = `
                <div id="${toastId}" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true"
                     style="background-color: ${bgColor}; color: ${textColor}; min-width: 300px;">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2">
                            <i data-feather="${iconName}" style="width:18px;height:18px;"></i>
                            <span>${message}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', toastHTML);
            const toastEl = document.getElementById(toastId);
            if (typeof feather !== 'undefined' && typeof feather.replace === 'function') {
                feather.replace();
            }
            const toast = new bootstrap.Toast(toastEl, {
                delay: duration
            });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', function() {
                toastEl.remove();
            });
        };

        const csrfTokenEndpoint = @json(route('csrf.token'));
        window.__nativeFetch = window.__nativeFetch || window.fetch.bind(window);
        let isRecoveringFromExpiredSession = false;
        let csrfRefreshPromise = null;

        const resolveRequestUrl = function(resource) {
            try {
                if (typeof Request !== 'undefined' && resource instanceof Request) {
                    return new URL(resource.url, window.location.href);
                }

                if (typeof resource === 'string') {
                    return new URL(resource, window.location.href);
                }
            } catch (error) {
                return null;
            }

            return null;
        };

        const isSameOriginRequest = function(resource) {
            const requestUrl = resolveRequestUrl(resource);
            if (!requestUrl) {
                return false;
            }

            return requestUrl.origin === window.location.origin;
        };

        const resolveRequestMethod = function(resource, init) {
            if (init?.method) {
                return String(init.method).toUpperCase();
            }

            if (typeof Request !== 'undefined' && resource instanceof Request && resource.method) {
                return String(resource.method).toUpperCase();
            }

            return 'GET';
        };

        const applyCsrfToken = function(token) {
            if (!token) {
                return;
            }

            const csrfMetaTag = document.querySelector('meta[name="csrf-token"]');
            if (csrfMetaTag) {
                csrfMetaTag.setAttribute('content', token);
            }

            document.querySelectorAll('input[name="_token"]').forEach(function(input) {
                input.value = token;
            });
        };

        const getCurrentCsrfToken = function() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        };

        const applyTokenToRequestData = function(data, token) {
            if (!data || !token) {
                return data;
            }

            if (typeof FormData !== 'undefined' && data instanceof FormData) {
                const clonedFormData = new FormData();
                data.forEach(function(value, key) {
                    clonedFormData.append(key, value);
                });

                if (clonedFormData.has('_token')) {
                    clonedFormData.set('_token', token);
                }

                return clonedFormData;
            }

            if (typeof URLSearchParams !== 'undefined' && data instanceof URLSearchParams) {
                const clonedSearchParams = new URLSearchParams(data.toString());
                if (clonedSearchParams.has('_token')) {
                    clonedSearchParams.set('_token', token);
                }

                return clonedSearchParams;
            }

            if (typeof data === 'string') {
                try {
                    const jsonPayload = JSON.parse(data);
                    if (jsonPayload && typeof jsonPayload === 'object') {
                        jsonPayload._token = token;

                        return JSON.stringify(jsonPayload);
                    }
                } catch (error) {
                    // Not JSON; continue to URL-encoded fallback.
                }

                try {
                    const formPayload = new URLSearchParams(data);
                    if (formPayload.has('_token')) {
                        formPayload.set('_token', token);

                        return formPayload.toString();
                    }
                } catch (error) {
                    return data;
                }
            }

            if (typeof data === 'object' && !Array.isArray(data)) {
                return {
                    ...data,
                    _token: token,
                };
            }

            return data;
        };

        window.refreshCsrfToken = async function() {
            if (csrfRefreshPromise) {
                return csrfRefreshPromise;
            }

            csrfRefreshPromise = (async function() {
                const response = await window.__nativeFetch(csrfTokenEndpoint, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Unable to refresh CSRF token.');
                }

                const payload = await response.json();
                if (!payload?.token) {
                    throw new Error('CSRF token was not returned.');
                }

                applyCsrfToken(payload.token);

                return payload.token;
            })();

            try {
                return await csrfRefreshPromise;
            } finally {
                csrfRefreshPromise = null;
            }
        };

        window.handleSessionExpired = function() {
            if (isRecoveringFromExpiredSession) {
                return;
            }

            isRecoveringFromExpiredSession = true;
            window.showToast('Session expired. Reloading to continue...', 'warning', 2500);

            setTimeout(function() {
                window.location.reload();
            }, 900);
        };

        const installJqueryCsrfRecovery = function() {
            if (!window.jQuery || window.__csrfJqueryRecoveryInstalled) {
                return;
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                beforeSend: function(xhr) {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    xhr.setRequestHeader('X-CSRF-TOKEN', token);
                },
            });

            $(document).ajaxError(function(event, jqXHR) {
                if (jqXHR?.status === 419) {
                    window.handleSessionExpired();
                }
            });

            window.__csrfJqueryRecoveryInstalled = true;
        };

        const installFetchCsrfRecovery = function() {
            if (window.__csrfFetchRecoveryInstalled || typeof window.fetch !== 'function') {
                return;
            }

            const nativeFetch = window.__nativeFetch || window.fetch.bind(window);
            window.fetch = async function(resource, init) {
                const requestInit = init ? Object.assign({}, init) : {};
                const alreadyRetried = Boolean(requestInit.__csrfRetried);
                delete requestInit.__csrfRetried;
                const sameOriginRequest = isSameOriginRequest(resource);
                const requestMethod = resolveRequestMethod(resource, requestInit);
                const isUnsafeMethod = !['GET', 'HEAD', 'OPTIONS', 'TRACE'].includes(requestMethod);
                const canRetry = sameOriginRequest &&
                    isUnsafeMethod &&
                    !(typeof Request !== 'undefined' && resource instanceof Request);

                if (sameOriginRequest) {
                    const requestHeaders = new Headers(requestInit.headers || {});
                    if (!requestHeaders.has('X-Requested-With')) {
                        requestHeaders.set('X-Requested-With', 'XMLHttpRequest');
                    }

                    if (isUnsafeMethod && !requestHeaders.has('X-CSRF-TOKEN') && !requestHeaders.has('X-XSRF-TOKEN')) {
                        const token = getCurrentCsrfToken();
                        if (token) {
                            requestHeaders.set('X-CSRF-TOKEN', token);
                        }
                    }

                    requestInit.headers = requestHeaders;
                }

                const response = await nativeFetch(resource, requestInit);
                if (response.status !== 419) {
                    return response;
                }

                if (!sameOriginRequest) {
                    return response;
                }

                if (!canRetry || alreadyRetried) {
                    window.handleSessionExpired();

                    return response;
                }

                try {
                    const token = await window.refreshCsrfToken();
                    const retryInit = Object.assign({}, requestInit, {
                        __csrfRetried: true,
                    });
                    const retryHeaders = new Headers(retryInit.headers || {});
                    retryHeaders.set('X-CSRF-TOKEN', token);
                    retryHeaders.set('X-Requested-With', 'XMLHttpRequest');
                    retryInit.headers = retryHeaders;
                    retryInit.body = applyTokenToRequestData(retryInit.body, token);

                    const retriedResponse = await nativeFetch(resource, retryInit);
                    if (retriedResponse.status === 419) {
                        window.handleSessionExpired();
                    }

                    return retriedResponse;
                } catch (error) {
                    window.handleSessionExpired();

                    return response;
                }
            };

            window.__csrfFetchRecoveryInstalled = true;
        };

        const installLivewireCsrfRecovery = function() {
            if (window.__csrfLivewireRecoveryInstalled) {
                return;
            }

            if (!window.Livewire || typeof window.Livewire.hook !== 'function') {
                return;
            }

            window.Livewire.hook('request', function(payload) {
                const fail = payload?.fail;

                if (typeof fail === 'function') {
                    fail(function(error) {
                        if (Number(error?.status) === 419) {
                            window.handleSessionExpired();
                        }
                    });

                    return;
                }

                if (Number(fail?.status) === 419) {
                    window.handleSessionExpired();
                }
            });

            window.__csrfLivewireRecoveryInstalled = true;
        };

        // Auto-scroll to session alerts so they are always visible
        document.addEventListener('DOMContentLoaded', function() {
            installJqueryCsrfRecovery();
            installFetchCsrfRecovery();
            installLivewireCsrfRecovery();

            const alert = document.querySelector('.session-alert');
            if (alert) {
                alert.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        });

        document.addEventListener('livewire:init', function() {
            installLivewireCsrfRecovery();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const oldInputState = @json(session()->getOldInput() ?? []);
            const errorMessagesState = @json($errors->messages());
            window.__modalFormState = {
                oldInputState,
                errorMessagesState
            };

            const hasOldInput = oldInputState && Object.keys(oldInputState).length > 0;
            const hasErrors = errorMessagesState && Object.keys(errorMessagesState).length > 0;
            if (!hasOldInput && !hasErrors) {
                return;
            }

            let targetForm = null;

            const escapeSelectorName = function(name) {
                if (window.CSS && typeof window.CSS.escape === 'function') {
                    return window.CSS.escape(name);
                }

                return name.replace(/([!"#$%&'()*+,./:;<=>?@[\]^`{|}~\\])/g, '\\$1');
            };

            const dotToBracketName = function(key) {
                const parts = String(key).split('.');
                if (parts.length <= 1) {
                    return String(key);
                }

                return parts.reduce(function(carry, part, index) {
                    return index === 0 ? part : carry + '[' + part + ']';
                }, '');
            };

            const normalizeNameToDot = function(name) {
                return String(name)
                    .replace(/\[\]/g, '')
                    .replace(/\]/g, '')
                    .replace(/\[/g, '.')
                    .replace(/^\./, '');
            };

            const flattenObject = function(value, prefix, output) {
                if (Array.isArray(value)) {
                    if (prefix) {
                        output[prefix] = value;
                    }

                    value.forEach(function(item, index) {
                        const key = prefix ? prefix + '.' + index : String(index);
                        flattenObject(item, key, output);
                    });

                    return;
                }

                if (value !== null && typeof value === 'object') {
                    Object.keys(value).forEach(function(key) {
                        const nestedPrefix = prefix ? prefix + '.' + key : key;
                        flattenObject(value[key], nestedPrefix, output);
                    });

                    return;
                }

                if (prefix) {
                    output[prefix] = value;
                }
            };

            const isTruthyValue = function(value) {
                if (typeof value === 'boolean') {
                    return value;
                }

                const normalized = String(value).toLowerCase();

                return ['1', 'true', 'yes', 'on'].includes(normalized);
            };

            const applyFieldValue = function(field, value, fieldIndexes) {
                if (!field || value === undefined || value === null) {
                    return;
                }

                const fieldType = String(field.type || '').toLowerCase();

                if (fieldType === 'password' || fieldType === 'file') {
                    return;
                }

                if (field.tagName === 'SELECT' && field.multiple) {
                    const values = Array.isArray(value) ? value.map(String) : [String(value)];
                    Array.from(field.options).forEach(function(option) {
                        option.selected = values.includes(String(option.value));
                    });

                    return;
                }

                if (fieldType === 'checkbox') {
                    if (Array.isArray(value)) {
                        field.checked = value.map(String).includes(String(field.value));

                        return;
                    }

                    if (field.value && field.value !== 'on') {
                        field.checked = String(value) === String(field.value);
                    } else {
                        field.checked = isTruthyValue(value);
                    }

                    return;
                }

                if (fieldType === 'radio') {
                    field.checked = String(value) === String(field.value);

                    return;
                }

                if (field.name.endsWith('[]') && Array.isArray(value)) {
                    const indexKey = field.name;
                    const index = fieldIndexes[indexKey] || 0;
                    const itemValue = value[index];

                    if (itemValue !== undefined && itemValue !== null && typeof itemValue !== 'object') {
                        field.value = itemValue;
                    }

                    fieldIndexes[indexKey] = index + 1;

                    return;
                }

                if (typeof value !== 'object') {
                    field.value = value;
                }
            };

            const queryFormFields = function(scope) {
                const root = scope || document;

                return root.querySelectorAll('form input[name], form select[name], form textarea[name]');
            };

            if (!hasErrors) {
                return;
            }

            const getFieldsByErrorKey = function(key, scope) {
                const candidates = new Set([
                    String(key),
                    dotToBracketName(key),
                    String(key).split('.')[0] + '[]',
                ]);

                const root = scope || document;
                const matches = [];
                candidates.forEach(function(candidate) {
                    const escapedName = escapeSelectorName(candidate);
                    root.querySelectorAll('[name="' + escapedName + '"]').forEach(function(field) {
                        matches.push(field);
                    });
                });

                return Array.from(new Set(matches));
            };

            const resolveVisibleField = function(field) {
                if (!field) {
                    return null;
                }

                const fieldType = String(field.type || '').toLowerCase();
                if (fieldType !== 'hidden') {
                    return field;
                }

                const container = field.closest(
                    '.mb-3, .form-group, .input-group, .position-relative, .col, [class*="col-"]'
                ) || field.parentElement;

                if (!container) {
                    return field;
                }

                const preferredVisibleControl = container.querySelector(
                    'select, input:not([type="hidden"]), textarea, button.dropdown-toggle, [data-bs-toggle="dropdown"]'
                );

                return preferredVisibleControl || field;
            };

            if (hasErrors) {
                Object.keys(errorMessagesState).some(function(key) {
                    const fields = getFieldsByErrorKey(key, document);
                    const fieldWithForm = fields.find(function(field) {
                        return Boolean(field.closest('form'));
                    });

                    if (fieldWithForm) {
                        targetForm = fieldWithForm.closest('form');

                        return true;
                    }

                    return false;
                });
            }

            if (hasOldInput) {
                const flattenedOldInput = {};
                flattenObject(oldInputState, '', flattenedOldInput);

                const fieldIndexes = {};
                queryFormFields(targetForm || document).forEach(function(field) {
                    const name = field.getAttribute('name');
                    if (!name || name === '_token' || name === '_method') {
                        return;
                    }

                    const dotName = normalizeNameToDot(name);
                    const oldValue = flattenedOldInput[dotName];
                    if (oldValue === undefined) {
                        return;
                    }

                    applyFieldValue(field, oldValue, fieldIndexes);
                });
            }

            let modalToOpen = null;

            Object.keys(errorMessagesState).forEach(function(key) {
                const messageList = errorMessagesState[key] || [];
                const firstMessage = Array.isArray(messageList) && messageList.length > 0 ? messageList[0] : null;
                const fields = getFieldsByErrorKey(key, targetForm || document);

                fields.forEach(function(field) {
                    if (targetForm && field.closest('form') !== targetForm) {
                        return;
                    }

                    const visibleField = resolveVisibleField(field);
                    if (!visibleField) {
                        return;
                    }

                    visibleField.classList.add('is-invalid');
                    if (visibleField.tagName === 'BUTTON') {
                        visibleField.classList.add('border', 'border-danger');
                    }
                    field.setAttribute('aria-invalid', 'true');

                    const feedbackTarget = visibleField.closest('.input-group') || visibleField;
                    const feedbackFieldName = visibleField.getAttribute('name') || field.getAttribute('name') || key;
                    const escapedFeedbackFieldName = escapeSelectorName(feedbackFieldName);
                    const existingFeedback = feedbackTarget.parentElement
                        ? feedbackTarget.parentElement.querySelector(
                            '.invalid-feedback.auto-invalid-feedback[data-error-for="' + escapedFeedbackFieldName + '"]'
                        )
                        : null;

                    if (firstMessage && !existingFeedback) {
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback d-block auto-invalid-feedback';
                        feedback.setAttribute('data-error-for', feedbackFieldName);
                        feedback.textContent = firstMessage;
                        feedbackTarget.insertAdjacentElement('afterend', feedback);
                    }

                    if (!modalToOpen) {
                        const modal = field.closest('.modal');
                        if (modal) {
                            modalToOpen = modal;
                        }
                    }
                });
            });

            if (modalToOpen && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalToOpen).show();
            }
        });
    </script>
</body>

</html>
