<style>
    .wiki-page {
        background: #f5f7fb;
    }
    .wiki-shell {
        min-height: 100dvh;
    }
    .wiki-layout {
        max-width: 1320px;
        margin: 0 auto;
    }
    .wiki-panel {
        background: #fff;
        border: 1px solid #e9edf5;
        border-radius: 14px;
        box-shadow: 0 6px 24px rgba(31, 42, 68, 0.05);
    }
    .wiki-header-title {
        font-size: clamp(1.25rem, 1.7vw, 1.8rem);
        font-weight: 650;
        letter-spacing: -0.01em;
        margin: 0;
    }
    .wiki-header-subtitle {
        color: #6b7280;
        margin: 0.35rem 0 0;
        font-size: 0.94rem;
    }
    .wiki-sidebar {
        position: sticky;
        top: 86px;
    }
    .wiki-sidebar-title {
        font-size: 0.76rem;
        letter-spacing: 0.08em;
        font-weight: 700;
        color: #8a94a6;
        text-transform: uppercase;
        margin-bottom: 0.9rem;
    }
    .wiki-tree-root,
    .wiki-tree-nested {
        margin: 0;
        padding-left: 0;
        list-style: none;
    }
    .wiki-tree-nested {
        margin-top: 0.25rem;
        margin-left: 0.5rem;
        padding-left: 0.75rem;
        border-left: 1px solid #e5eaf3;
    }
    .wiki-tree-link {
        display: inline-block;
        padding: 0.16rem 0.1rem;
        border-radius: 6px;
        color: #334155;
        text-decoration: none;
        font-size: 0.94rem;
    }
    .wiki-tree-link:hover {
        color: #0d6efd;
    }
    .wiki-tree-link.active {
        color: #0d6efd;
        font-weight: 600;
    }
    .wiki-list-item {
        border-bottom: 1px solid #eef2f8;
        padding: 0.75rem 0.1rem;
    }
    .wiki-list-item:last-child {
        border-bottom: 0;
    }
    .wiki-slug {
        color: #95a0b3;
        font-size: 0.78rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
    .wiki-content {
        font-size: 1.02rem;
        line-height: 1.7;
        color: #1f2937;
    }
    .wiki-content h1, .wiki-content h2, .wiki-content h3, .wiki-content h4 {
        margin-top: 1.2rem;
        margin-bottom: 0.75rem;
        font-weight: 650;
        color: #0f172a;
    }
    .wiki-content h1 {
        font-size: 1.72rem;
        border-bottom: 1px solid #e8edf5;
        padding-bottom: 0.35rem;
    }
    .wiki-content h2 { font-size: 1.38rem; }
    .wiki-content pre {
        background: #f6f8fc;
        border: 1px solid #e8edf5;
        border-radius: 10px;
        padding: 1rem;
        overflow-x: auto;
        font-size: 0.9rem;
    }
    .wiki-content code {
        background: #eef2f8;
        padding: 0.14rem 0.35rem;
        border-radius: 4px;
        font-size: 0.9em;
    }
    .wiki-content pre code {
        background: none;
        padding: 0;
    }
    .wiki-content table {
        width: 100%;
        margin: 1rem 0;
        border-collapse: collapse;
    }
    .wiki-content table th,
    .wiki-content table td {
        border: 1px solid #e2e8f0;
        padding: 0.5rem 0.75rem;
    }
    .wiki-content table th {
        background: #f8fafc;
    }
    .wiki-content blockquote {
        border-left: 3px solid #3b82f6;
        margin: 1rem 0;
        padding: 0.5rem 0.95rem;
        background: #f8fafc;
        border-radius: 0 8px 8px 0;
    }
    .wiki-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }
    .wiki-content ul, .wiki-content ol {
        padding-left: 1.35rem;
    }
    .wiki-form .form-label {
        font-weight: 600;
        color: #334155;
    }
    .wiki-form .form-control,
    .wiki-form .form-select {
        border-color: #dce3ef;
        border-radius: 10px;
        padding: 0.58rem 0.72rem;
    }
    .wiki-form .form-control:focus,
    .wiki-form .form-select:focus {
        box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.14);
        border-color: #0d6efd;
    }
    @media (max-width: 991.98px) {
        .wiki-sidebar {
            position: static;
        }
        .wiki-panel {
            border-radius: 12px;
        }
    }
</style>
