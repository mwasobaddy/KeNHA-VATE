<?php

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Idea;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {
    public $idea;

    /**
     * Mount the component with the idea
     */
    public function mount($idea = null): void
    {
        // Try exact slug match first
        $ideaModel = Idea::where('slug', $idea)->first();

        // Fallback: case-insensitive slug match (SQLite/MySQL collations differ)
        if (!$ideaModel) {
            $lower = mb_strtolower($idea);
            $ideaModel = Idea::whereRaw('lower(slug) = ?', [$lower])->first();
        }

        // Fallback: if the incoming param is numeric, try id lookup
        if (!$ideaModel && is_numeric($idea)) {
            $ideaModel = Idea::find((int) $idea);
        }

        // Ensure the $idea property is always set in the mount method
        if (!$ideaModel) {
            abort(404, 'Idea not found');
        }

        // Ensure the user owns this idea
        if ($ideaModel->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $this->idea = $ideaModel;
    }

    /**
     * Go back to idea details
     */
    public function backToIdea(): void
    {
        $this->redirect(route('ideas.show', $this->idea->slug), navigate: true);
    }

    /**
     * Download the PDF
     */
    public function downloadPdf(): void
    {
        $this->redirect(route('ideas.pdf', $this->idea->slug));
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-[#F8EBD5]/20 via-white to-[#F8EBD5] dark:from-zinc-900/20 dark:via-zinc-800 dark:to-zinc-900">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 p-6 mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <flux:button
                        wire:click="backToIdea"
                        variant="ghost"
                        class="text-[#231F20] dark:text-white hover:bg-[#F8EBD5] dark:hover:bg-zinc-700"
                    >
                        <flux:icon name="arrow-left" class="w-5 h-5 mr-2" />
                        Back to Idea
                    </flux:button>

                    <div class="inline-flex items-center justify-center p-3 rounded-full bg-gradient-to-br from-[#FFF200] to-yellow-300 dark:from-yellow-400 dark:to-yellow-500 shadow-lg border-2 border-[#231F20] dark:border-zinc-700">
                        <flux:icon name="document-text" class="w-6 h-6 text-[#231F20] dark:text-zinc-900" />
                    </div>

                    <div>
                        <h1 class="text-2xl font-bold text-[#231F20] dark:text-white">
                            PDF Preview: {{ $idea->idea_title }}
                        </h1>
                        <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 mt-1">
                            Previewing attachment: {{ $idea->attachment_filename ?? 'idea.pdf' }}
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <flux:button
                        wire:click="downloadPdf"
                        variant="primary"
                        class="bg-[#FFF200] hover:bg-yellow-400 text-[#231F20] dark:bg-yellow-500 dark:hover:bg-yellow-600"
                    >
                        <flux:icon name="arrow-down-tray" class="w-4 h-4 mr-2" />
                        Download PDF
                    </flux:button>
                    @if (app()->environment('local'))
                    <!-- Debug: show response headers for the pdf endpoint (local only) -->
                    <flux:button
                        id="show-headers-btn"
                        variant="outline"
                        size="sm"
                        class="border-zinc-300 text-zinc-800 dark:text-white"
                    >
                        <flux:icon name="bug-ant" class="w-4 h-4 mr-1" />
                        Show headers
                    </flux:button>
                    <flux:button
                        id="fetch-load-btn"
                        variant="outline"
                        size="sm"
                        class="border-zinc-300 text-zinc-800 dark:text-white"
                    >
                        <flux:icon name="arrow-down-tray" class="w-4 h-4 mr-1" />
                        Load via fetch
                    </flux:button>
                    @endif
                </div>
            </div>
        </div>

        <!-- PDF Viewer -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-[#9B9EA4]/20 dark:border-zinc-700 overflow-hidden">
            <div class="p-6 border-b border-[#9B9EA4]/20 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-[#231F20] dark:text-white">
                    PDF Document Viewer
                </h2>
            </div>

            <div class="p-6">
                <iframe
                    src=""
                    class="w-full h-[800px] border border-[#9B9EA4]/20 dark:border-zinc-700 rounded-lg"
                    allowfullscreen
                ></iframe>
                
                <!-- Debug output container -->
                @if (app()->environment('local'))
                <div id="pdf-headers" class="mt-4 bg-gray-50 dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded p-4 text-sm text-zinc-700 dark:text-zinc-300 hidden">
                    <div class="font-medium mb-2">PDF endpoint headers (debug)</div>
                    <div id="pdf-headers-status" class="mb-2"></div>
                    <pre id="pdf-headers-pre" class="whitespace-pre-wrap overflow-auto text-xs"></pre>
                    <div id="pdf-progress" class="mt-3 hidden">
                        <div class="text-xs mb-1">Download progress: <span id="pdf-bytes">0</span> / <span id="pdf-total">?</span> bytes</div>
                        <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded h-2 overflow-hidden">
                            <div id="pdf-progress-bar" class="h-2 bg-yellow-400" style="width:0%"></div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

    </div>

    <style>
        /* Custom Scrollbar for better UX */
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #F8EBD5;
        }

        ::-webkit-scrollbar-thumb {
            background: #FFF200;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #F4E000;
        }

        .dark ::-webkit-scrollbar-track {
            background: #374151;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #F59E0B;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #D97706;
        }

        /* Smooth transitions for all interactive elements */
        * {
            transition: all 0.2s ease-in-out;
        }
    </style>
</div>

<script>
    (function () {
        const btn = document.getElementById('show-headers-btn');
        const container = document.getElementById('pdf-headers');
        const pre = document.getElementById('pdf-headers-pre');
        const statusDiv = document.getElementById('pdf-headers-status');
        const fetchBtn = document.getElementById('fetch-load-btn');
        const iframe = document.querySelector('iframe');

        // Show headers (local only)
        if (btn) {
            btn.addEventListener('click', async function () {
            const url = "{{ route('ideas.pdf', $idea->slug) }}";
                // show loading
                btn.setAttribute('disabled', 'disabled');
                btn.innerText = 'Loading...';

                try {
                    // Use HEAD to avoid downloading body; include credentials for session auth
                    const res = await fetch(url, {
                        method: 'HEAD',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (container) container.classList.remove('hidden');
                    if (statusDiv) statusDiv.textContent = `Status: ${res.status} ${res.statusText}`;

                    // Collect headers
                    let headersText = '';
                    for (let pair of res.headers.entries()) {
                        headersText += `${pair[0]}: ${pair[1]}\n`;
                    }

                    if (pre) pre.textContent = headersText || 'No headers returned';
                } catch (err) {
                    if (container) container.classList.remove('hidden');
                    if (statusDiv) statusDiv.textContent = 'Network error while fetching headers';
                    if (pre) pre.textContent = String(err);
                } finally {
                    btn.removeAttribute('disabled');
                    btn.innerText = 'Show headers';
                }
            });
        }

        // Helper: stream-fetch and update progress
        let _currentBlobUrl = null;

        async function streamFetchToIframe(url) {
            const res = await fetch(url, { credentials: 'same-origin' });
            if (!res.ok) throw new Error(`Request failed: ${res.status}`);

            const contentLength = res.headers.get('content-length');
            const total = contentLength ? parseInt(contentLength, 10) : null;

            // show progress UI
            const progressWrap = document.getElementById('pdf-progress');
            const progressBar = document.getElementById('pdf-progress-bar');
            const bytesEl = document.getElementById('pdf-bytes');
            const totalEl = document.getElementById('pdf-total');

            if (progressWrap) progressWrap.classList.remove('hidden');
            if (totalEl) totalEl.textContent = total ?? '?';

            const reader = res.body.getReader();
            const chunks = [];
            let received = 0;

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                chunks.push(value);
                received += value.length;
                if (bytesEl) bytesEl.textContent = received;
                if (progressBar && total) {
                    progressBar.style.width = `${Math.round((received / total) * 100)}%`;
                }
            }

            // Combine chunks into a single blob
            const blob = new Blob(chunks, { type: res.headers.get('content-type') || 'application/pdf' });
            const blobUrl = URL.createObjectURL(blob);

            // Revoke previous blob if present
            if (_currentBlobUrl) {
                try { URL.revokeObjectURL(_currentBlobUrl); } catch (e) { /* ignore */ }
            }
            _currentBlobUrl = blobUrl;

            // Set iframe src and display info
            iframe.src = blobUrl;
            container.classList.remove('hidden');
            statusDiv.textContent = `Loaded via streaming fetch: ${res.status} ${res.statusText}`;
            pre.textContent = `Fetched bytes: ${received}\nContent-Type: ${res.headers.get('content-type')}`;

            // Revoke blob URL when page unloads
            window.addEventListener('beforeunload', () => {
                if (_currentBlobUrl) try { URL.revokeObjectURL(_currentBlobUrl); } catch (e) {}
            });
        }

        // Auto-run fetch on page load (after small delay to allow UI to mount)
        (function autoLoad() {
            const url = "{{ route('ideas.pdf', $idea->slug) }}";
            // attempt streaming fetch; fallback to simple fetch on errors
            setTimeout(async () => {
                try {
                    // show quick indicator on the button
                    if (fetchBtn) {
                        fetchBtn.setAttribute('disabled', 'disabled');
                        fetchBtn.innerText = 'Loading...';
                    }
                    await streamFetchToIframe(url);
                } catch (err) {
                    // fallback: simple fetch->blob
                    try {
                        const res = await fetch(url, { credentials: 'same-origin' });
                        const blob = await res.blob();
                        const blobUrl = URL.createObjectURL(blob);

                        // Revoke previous blob if present
                        if (_currentBlobUrl) try { URL.revokeObjectURL(_currentBlobUrl); } catch (e) {}
                        _currentBlobUrl = blobUrl;

                        iframe.src = blobUrl;
                        if (container) container.classList.remove('hidden');
                        if (statusDiv) statusDiv.textContent = `Loaded via fallback fetch: ${res.status} ${res.statusText}`;
                        if (pre) pre.textContent = `Fetched bytes: ${blob.size}\nContent-Type: ${res.headers.get('content-type')}`;
                        window.addEventListener('beforeunload', () => { if (_currentBlobUrl) try { URL.revokeObjectURL(_currentBlobUrl); } catch (e) {} });
                    } catch (err2) {
                        container.classList.remove('hidden');
                        statusDiv.textContent = 'Auto-load failed';
                        pre.textContent = String(err2 || err);
                    }
                } finally {
                    if (fetchBtn) {
                        fetchBtn.removeAttribute('disabled');
                        fetchBtn.innerText = 'Load via fetch';
                    }
                }
            }, 350);
        })();
    })();
</script>