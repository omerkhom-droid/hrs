<style>
  .attendance-page {
    --att-primary: #1d4ed8;
    --att-border: #e2e8f0;
    --att-muted: #64748b;
    color: #0f172a;
  }

  .attendance-page .att-card {
    background: #fff;
    border: 1px solid var(--att-border);
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
  }

  .attendance-page .att-summary {
    min-height: 105px;
    padding: 18px;
  }

  .attendance-page .att-summary-label {
    color: var(--att-muted);
    font-size: 12px;
  }

  .attendance-page .att-summary-value {
    margin-top: 7px;
    font-size: 26px;
    font-weight: 800;
  }

  .attendance-page .att-summary-icon {
    display: grid;
    width: 42px;
    height: 42px;
    place-items: center;
    border-radius: 13px;
    font-size: 19px;
    font-weight: 800;
  }

  .attendance-page .form-label,
  .attendance-page .filter-label {
    margin-bottom: 7px;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
  }

  .attendance-page .form-control,
  .attendance-page .form-select {
    min-height: 42px;
    border-color: #dbe3ef;
    border-radius: 10px;
  }

  .attendance-page textarea.form-control {
    min-height: 88px;
  }

  .attendance-page .table > :not(caption) > * > * {
    padding: 12px;
    vertical-align: middle;
    border-color: #edf2f7;
  }

  .attendance-page .table thead th {
    white-space: nowrap;
    color: #475569;
    background: #f8fafc;
    font-size: 13px;
  }

  .attendance-page .att-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
  }

  .attendance-page .att-success {
    color: #047857;
    background: #d1fae5;
  }

  .attendance-page .att-warning {
    color: #b45309;
    background: #fef3c7;
  }

  .attendance-page .att-danger {
    color: #b91c1c;
    background: #fee2e2;
  }

  .attendance-page .att-info {
    color: #1d4ed8;
    background: #dbeafe;
  }

  .attendance-page .att-neutral {
    color: #475569;
    background: #e2e8f0;
  }

  .attendance-page .employee-name {
    font-weight: 800;
  }

  .attendance-page .att-meta {
    margin-top: 3px;
    color: var(--att-muted);
    font-size: 12px;
  }

  .attendance-page .att-loading,
  .attendance-page .att-empty {
    padding: 45px 20px;
    text-align: center;
    color: var(--att-muted);
  }

  .attendance-page .pagination .page-link {
    min-width: 38px;
    margin: 0 2px;
    border-radius: 9px;
    text-align: center;
  }

  .att-modal-overlay {
    position: fixed;
    z-index: 1080;
    inset: 0;
    display: none;
    overflow-x: hidden;
    overflow-y: auto;
    padding: 28px 14px;
    background: rgba(15, 23, 42, 0.64);
  }

  .att-modal-dialog {
    width: min(900px, 100%);
    margin: 0 auto;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
  }

  .att-modal-dialog.att-modal-sm {
    width: min(500px, 100%);
  }

  .att-modal-header,
  .att-modal-footer {
    position: sticky;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 22px;
    background: #fff;
  }

  .att-modal-header {
    top: -28px;
    border-bottom: 1px solid var(--att-border);
    border-radius: 18px 18px 0 0;
  }

  .att-modal-footer {
    bottom: -28px;
    justify-content: flex-end;
    border-top: 1px solid var(--att-border);
    border-radius: 0 0 18px 18px;
  }

  .att-modal-body {
    padding: 22px;
  }

  .att-modal-close {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 10px;
    color: #475569;
    background: #f1f5f9;
    font-size: 22px;
    line-height: 1;
  }

  body.att-modal-open {
    overflow: hidden;
  }

  .attendance-page .att-detail {
    height: 100%;
    padding: 14px;
    border: 1px solid #e8edf5;
    border-radius: 12px;
    background: #fbfdff;
  }

  .attendance-page .att-detail-label {
    margin-bottom: 7px;
    color: var(--att-muted);
    font-size: 12px;
  }

  .attendance-page .att-detail-value {
    overflow-wrap: anywhere;
    font-weight: 700;
  }

  .att-toast {
    position: fixed;
    z-index: 1100;
    top: 24px;
    left: 24px;
    display: none;
    max-width: 420px;
    padding: 14px 18px;
    border-radius: 12px;
    color: #fff;
    box-shadow: 0 14px 36px rgba(15, 23, 42, 0.24);
  }

  .att-toast.success {
    background: #047857;
  }

  .att-toast.error {
    background: #b91c1c;
  }

  .attendance-page .day-check {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 11px;
    border: 1px solid #dbe3ef;
    border-radius: 10px;
    background: #fff;
  }

  @media (max-width: 767.98px) {
    .att-modal-overlay {
      padding: 0;
    }

    .att-modal-dialog,
    .att-modal-dialog.att-modal-sm {
      width: 100%;
      min-height: 100vh;
      border-radius: 0;
    }

    .att-modal-header {
      top: 0;
      border-radius: 0;
    }

    .att-modal-footer {
      bottom: 0;
      border-radius: 0;
    }
  }
</style>
