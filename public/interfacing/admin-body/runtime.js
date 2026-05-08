// Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
// Interfacing admin body hydration handshake.
// This module intentionally does not implement a native UI. It discovers the
// machine-readable schema, enforces the provider selection policy, exposes a
// provider-neutral event, and lets the configured Ant Design ProComponents
// renderer mount. No Twig-rendered admin body UI is allowed.

const MOUNT_SELECTOR = '[data-interfacing-admin-body-mount="true"]';
const SCHEMA_SELECTOR = '[data-interfacing-admin-body-schema="true"]';
const READY_EVENT = 'interfacing:admin-body:ready';
const PROVIDER_POLICY_ERROR_EVENT = 'interfacing:admin-body:provider-policy-error';
const PROVIDER_REQUIRED_ERROR_EVENT = 'interfacing:admin-body:provider-required-error';
const PROVIDER_REGISTRY = 'InterfacingAdminBodyProviders';
const PROVIDER_REGISTRY_API = 'InterfacingAdminBodyProviderRegistry';
const PRIMARY_ADMIN_PROVIDER = 'antd-pro';
const SECONDARY_RICH_FACADE_PROVIDER = 'primereact';
const FORBIDDEN_SECONDARY_REPLACEMENT = 'forbidden-for-admin-body';
const HYDRATION_ATTR = 'adminBodyHydration';
const SCHEMA_MANIFEST_KEY = 'schemaManifest';
const SCHEMA_MANIFEST_ERROR_EVENT = 'interfacing:admin-body:schema-manifest-error';
const RESOURCE_CONTRACT_KEY = 'resourceContract';
const RESOURCE_CONTRACT_ERROR_EVENT = 'interfacing:admin-body:resource-contract-error';
const OPERATION_POLICY_KEY = 'operationPolicy';
const OPERATION_POLICY_ERROR_EVENT = 'interfacing:admin-body:operation-policy-error';
const TOOLBAR_POLICY_KEY = 'toolbarPolicy';
const TOOLBAR_POLICY_ERROR_EVENT = 'interfacing:admin-body:toolbar-policy-error';
const ROW_SELECTION_POLICY_KEY = 'rowSelectionPolicy';
const ROW_SELECTION_POLICY_ERROR_EVENT = 'interfacing:admin-body:row-selection-policy-error';
const TABLE_INTERACTION_POLICY_KEY = 'tableInteractionPolicy';
const TABLE_INTERACTION_POLICY_ERROR_EVENT = 'interfacing:admin-body:table-interaction-policy-error';
const EMPTY_STATE_POLICY_KEY = 'emptyStatePolicy';
const EMPTY_STATE_POLICY_ERROR_EVENT = 'interfacing:admin-body:empty-state-policy-error';
const FORM_LIFECYCLE_POLICY_KEY = 'formLifecyclePolicy';
const FORM_LIFECYCLE_POLICY_ERROR_EVENT = 'interfacing:admin-body:form-lifecycle-policy-error';
const DETAIL_VIEW_POLICY_KEY = 'detailViewPolicy';
const DETAIL_VIEW_POLICY_ERROR_EVENT = 'interfacing:admin-body:detail-view-policy-error';
const NAVIGATION_POLICY_KEY = 'navigationPolicy';
const NAVIGATION_POLICY_ERROR_EVENT = 'interfacing:admin-body:navigation-policy-error';
const AUTHORIZATION_POLICY_KEY = 'authorizationPolicy';
const AUTHORIZATION_POLICY_ERROR_EVENT = 'interfacing:admin-body:authorization-policy-error';
const TELEMETRY_POLICY_KEY = 'telemetryPolicy';
const TELEMETRY_POLICY_ERROR_EVENT = 'interfacing:admin-body:telemetry-policy-error';
const ACCESSIBILITY_POLICY_KEY = 'accessibilityPolicy';
const ACCESSIBILITY_POLICY_ERROR_EVENT = 'interfacing:admin-body:accessibility-policy-error';
const RESPONSIVE_LAYOUT_POLICY_KEY = 'responsiveLayoutPolicy';
const RESPONSIVE_LAYOUT_POLICY_ERROR_EVENT = 'interfacing:admin-body:responsive-layout-policy-error';
const HYDRATION_FAILED_EVENT = 'interfacing:admin-body:hydration-failed';
const PROVIDER_WAITING_ATTR = 'adminBodyProviderWaiting';
const PROVIDER_BOOT_MARKER_SELECTOR = '[data-interfacing-admin-body-provider-boot-marker="true"]';

function parseSchema(mount) {
  const schemaNode = mount.querySelector(SCHEMA_SELECTOR);

  if (!schemaNode) {
    return null;
  }

  try {
    return JSON.parse(schemaNode.textContent || '{}');
  } catch (error) {
    mount.dataset[HYDRATION_ATTR] = 'schema-error';
    mount.dispatchEvent(new CustomEvent('interfacing:admin-body:schema-error', {
      bubbles: true,
      detail: { error },
    }));

    return null;
  }
}

function assertSchemaManifest(mount, schema) {
  const manifest = schema?.[SCHEMA_MANIFEST_KEY];
  const missing = [];

  if (!manifest || typeof manifest !== 'object') {
    missing.push(SCHEMA_MANIFEST_KEY);
  } else {
    ['name', 'version', 'owner', 'requiredTopLevelKeys', 'requiredPolicyKeys', 'providerTargets', 'runtimeChecks'].forEach((key) => {
      if (!(key in manifest)) {
        missing.push(`${SCHEMA_MANIFEST_KEY}.${key}`);
      }
    });

    ['schema', 'version', 'providers', 'providerPolicy', 'resourceContract', 'operationPolicy', 'toolbarPolicy', 'rowSelectionPolicy', 'tableInteractionPolicy', 'emptyStatePolicy', 'formLifecyclePolicy', 'detailViewPolicy', 'navigationPolicy', 'authorizationPolicy', 'telemetryPolicy', 'accessibilityPolicy', 'responsiveLayoutPolicy', 'runtime', 'hydration'].forEach((key) => {
      if (!Array.isArray(manifest.requiredTopLevelKeys) || !manifest.requiredTopLevelKeys.includes(key)) {
        missing.push(`${SCHEMA_MANIFEST_KEY}.requiredTopLevelKeys.${key}`);
      }

      if (!(key in schema)) {
        missing.push(`schema.${key}`);
      }
    });

    ['providerPolicy', 'resourceContract', 'operationPolicy', 'toolbarPolicy', 'rowSelectionPolicy', 'tableInteractionPolicy', 'emptyStatePolicy', 'formLifecyclePolicy', 'detailViewPolicy', 'navigationPolicy', 'authorizationPolicy', 'telemetryPolicy', 'accessibilityPolicy', 'responsiveLayoutPolicy'].forEach((key) => {
      if (!Array.isArray(manifest.requiredPolicyKeys) || !manifest.requiredPolicyKeys.includes(key)) {
        missing.push(`${SCHEMA_MANIFEST_KEY}.requiredPolicyKeys.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'schema-manifest-error';
  mount.dispatchEvent(new CustomEvent(SCHEMA_MANIFEST_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}
function assertResourceContract(mount, schema) {
  const contract = schema?.[RESOURCE_CONTRACT_KEY];
  const missing = [];

  if (!contract || typeof contract !== 'object') {
    missing.push(RESOURCE_CONTRACT_KEY);
  } else {
    ['dataSource', 'columns', 'filters', 'formFields', 'headerActions', 'rowActions'].forEach((key) => {
      if (!(key in contract)) {
        missing.push(`${RESOURCE_CONTRACT_KEY}.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'resource-contract-error';
  mount.dispatchEvent(new CustomEvent(RESOURCE_CONTRACT_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}

function assertOperationPolicy(mount, schema) {
  const policy = schema?.[OPERATION_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(OPERATION_POLICY_KEY);
  } else {
    ['name', 'version', 'supportedOperations', 'currentOperation', 'headerActions', 'rowActions', 'destructiveActions', 'confirmation', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${OPERATION_POLICY_KEY}.${key}`);
      }
    });

    const currentOperation = policy.currentOperation;
    if (Array.isArray(policy.supportedOperations) && !policy.supportedOperations.includes(currentOperation)) {
      missing.push(`${OPERATION_POLICY_KEY}.supportedOperations.${currentOperation}`);
    }

    if (Array.isArray(policy.destructiveActions) && policy.destructiveActions.includes('delete')) {
      const deleteConfirmation = policy.confirmation?.delete;
      if (!deleteConfirmation || deleteConfirmation.required !== true) {
        missing.push(`${OPERATION_POLICY_KEY}.confirmation.delete.required`);
      }
    }
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'operation-policy-error';
  mount.dispatchEvent(new CustomEvent(OPERATION_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}


function assertToolbarPolicy(mount, schema) {
  const policy = schema?.[TOOLBAR_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(TOOLBAR_POLICY_KEY);
  } else {
    ['name', 'version', 'controls', 'search', 'filters', 'contentLocale', 'viewMode', 'bulkActions', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${TOOLBAR_POLICY_KEY}.${key}`);
      }
    });

    ['search', 'filters', 'content-locale', 'view-mode', 'bulk-actions'].forEach((control) => {
      if (!Array.isArray(policy.controls) || !policy.controls.includes(control)) {
        missing.push(`${TOOLBAR_POLICY_KEY}.controls.${control}`);
      }
    });

    if (policy.bulkActions?.mode !== 'guarded-by-row-selection') {
      missing.push(`${TOOLBAR_POLICY_KEY}.bulkActions.mode`);
    }
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'toolbar-policy-error';
  mount.dispatchEvent(new CustomEvent(TOOLBAR_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}


function assertRowSelectionPolicy(mount, schema) {
  const policy = schema?.[ROW_SELECTION_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(ROW_SELECTION_POLICY_KEY);
  } else {
    ['name', 'version', 'enabled', 'rowKey', 'selectionType', 'mode', 'bulkActions', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${ROW_SELECTION_POLICY_KEY}.${key}`);
      }
    });

    if (policy.mode !== 'guarded-by-row-selection') {
      missing.push(`${ROW_SELECTION_POLICY_KEY}.mode`);
    }

    if (policy.enabled === true && policy.bulkActions?.enabled === true) {
      const targets = policy.providerTargets || {};
      ['rowSelection', 'tableAlertOption', 'bulkActions'].forEach((key) => {
        if (!(key in targets)) {
          missing.push(`${ROW_SELECTION_POLICY_KEY}.providerTargets.${key}`);
        }
      });
    }

    const actions = policy.bulkActions?.actions;
    if (Array.isArray(actions)) {
      actions.forEach((action) => {
        if (action.destructive === true && action.confirmation !== 'confirmation-required') {
          missing.push(`${ROW_SELECTION_POLICY_KEY}.bulkActions.${action.key}.confirmation`);
        }
      });
    }
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'row-selection-policy-error';
  mount.dispatchEvent(new CustomEvent(ROW_SELECTION_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}

function assertTableInteractionPolicy(mount, schema) {
  const policy = schema?.[TABLE_INTERACTION_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(TABLE_INTERACTION_POLICY_KEY);
  } else {
    ['name', 'version', 'pagination', 'sorting', 'density', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${TABLE_INTERACTION_POLICY_KEY}.${key}`);
      }
    });

    if (policy.pagination?.mode !== 'server-driven') {
      missing.push(`${TABLE_INTERACTION_POLICY_KEY}.pagination.mode`);
    }

    if (policy.sorting?.mode !== 'server-driven') {
      missing.push(`${TABLE_INTERACTION_POLICY_KEY}.sorting.mode`);
    }

    const targets = policy.providerTargets || {};
    ['pagination', 'sorting', 'density'].forEach((key) => {
      if (!(key in targets)) {
        missing.push(`${TABLE_INTERACTION_POLICY_KEY}.providerTargets.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'table-interaction-policy-error';
  mount.dispatchEvent(new CustomEvent(TABLE_INTERACTION_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}


function assertEmptyStatePolicy(mount, schema) {
  const policy = schema?.[EMPTY_STATE_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(EMPTY_STATE_POLICY_KEY);
  } else {
    ['name', 'version', 'states', 'actions', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${EMPTY_STATE_POLICY_KEY}.${key}`);
      }
    });

    const states = policy.states || {};
    ['empty', 'loading', 'error', 'validationError', 'offline'].forEach((key) => {
      if (!(key in states)) {
        missing.push(`${EMPTY_STATE_POLICY_KEY}.states.${key}`);
      }
    });

    const targets = policy.providerTargets || {};
    ['empty', 'loading', 'error', 'validationError', 'offline'].forEach((key) => {
      if (!(key in targets)) {
        missing.push(`${EMPTY_STATE_POLICY_KEY}.providerTargets.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'empty-state-policy-error';
  mount.dispatchEvent(new CustomEvent(EMPTY_STATE_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}


function assertFormLifecyclePolicy(mount, schema) {
  const policy = schema?.[FORM_LIFECYCLE_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(FORM_LIFECYCLE_POLICY_KEY);
  } else {
    ['name', 'version', 'modes', 'submit', 'actions', 'dirtyState', 'validation', 'feedback', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${FORM_LIFECYCLE_POLICY_KEY}.${key}`);
      }
    });

    ['save', 'saveAndContinue', 'cancel', 'reset'].forEach((key) => {
      if (!(key in (policy.actions || {}))) {
        missing.push(`${FORM_LIFECYCLE_POLICY_KEY}.actions.${key}`);
      }
    });

    if (policy.submit?.mode !== 'server-driven') {
      missing.push(`${FORM_LIFECYCLE_POLICY_KEY}.submit.mode`);
    }

    if (policy.dirtyState?.guard !== 'confirm-on-navigate-away') {
      missing.push(`${FORM_LIFECYCLE_POLICY_KEY}.dirtyState.guard`);
    }

    const targets = policy.providerTargets || {};
    ['form', 'submitter', 'validation', 'dirtyConfirm', 'success', 'error'].forEach((key) => {
      if (!(key in targets)) {
        missing.push(`${FORM_LIFECYCLE_POLICY_KEY}.providerTargets.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'form-lifecycle-policy-error';
  mount.dispatchEvent(new CustomEvent(FORM_LIFECYCLE_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}


function assertDetailViewPolicy(mount, schema) {
  const policy = schema?.[DETAIL_VIEW_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(DETAIL_VIEW_POLICY_KEY);
  } else {
    ['name', 'version', 'mode', 'layout', 'sections', 'actions', 'destructiveActions', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${DETAIL_VIEW_POLICY_KEY}.${key}`);
      }
    });

    const sections = policy.sections || {};
    ['general', 'metadata', 'relations'].forEach((key) => {
      if (!(key in sections)) {
        missing.push(`${DETAIL_VIEW_POLICY_KEY}.sections.${key}`);
      }
    });

    const actions = policy.actions || {};
    ['backToList', 'edit', 'delete'].forEach((key) => {
      if (!(key in actions)) {
        missing.push(`${DETAIL_VIEW_POLICY_KEY}.actions.${key}`);
      }
    });

    if (Array.isArray(policy.destructiveActions) && policy.destructiveActions.includes('delete')) {
      if (policy.actions?.delete?.confirmation !== 'confirmation-required') {
        missing.push(`${DETAIL_VIEW_POLICY_KEY}.actions.delete.confirmation`);
      }
    }

    const targets = policy.providerTargets || {};
    ['page', 'descriptions', 'metadata', 'relations', 'actions', 'deleteConfirm'].forEach((key) => {
      if (!(key in targets)) {
        missing.push(`${DETAIL_VIEW_POLICY_KEY}.providerTargets.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'detail-view-policy-error';
  mount.dispatchEvent(new CustomEvent(DETAIL_VIEW_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}


function assertNavigationPolicy(mount, schema) {
  const policy = schema?.[NAVIGATION_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(NAVIGATION_POLICY_KEY);
  } else {
    ['name', 'version', 'scope', 'globalNavigationOwner', 'breadcrumbs', 'backAction', 'resourceContext', 'routeContext', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${NAVIGATION_POLICY_KEY}.${key}`);
      }
    });

    if (policy.globalNavigationOwner !== 'ecosystem-shell') {
      missing.push(`${NAVIGATION_POLICY_KEY}.globalNavigationOwner`);
    }

    const targets = policy.providerTargets || {};
    ['breadcrumbs', 'backAction', 'resourceContext', 'routeContext'].forEach((key) => {
      if (!(key in targets)) {
        missing.push(`${NAVIGATION_POLICY_KEY}.providerTargets.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'navigation-policy-error';
  mount.dispatchEvent(new CustomEvent(NAVIGATION_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}

function providerFromRegistry(providerName) {
  const registryApi = window[PROVIDER_REGISTRY_API];

  if (registryApi && typeof registryApi.get === 'function') {
    return registryApi.get(providerName);
  }

  const registry = window[PROVIDER_REGISTRY];

  if (!registry || typeof registry !== 'object') {
    return null;
  }

  return registry[providerName] || null;
}


function assertAuthorizationPolicy(mount, schema) {
  const policy = schema?.[AUTHORIZATION_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(AUTHORIZATION_POLICY_KEY);
  } else {
    ['name', 'version', 'mode', 'enforcementOwner', 'uiResponsibility', 'defaultDecision', 'actionGroups', 'deniedActionBehavior', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${AUTHORIZATION_POLICY_KEY}.${key}`);
      }
    });

    if (policy.defaultDecision !== 'disabled-until-authorized') {
      missing.push(`${AUTHORIZATION_POLICY_KEY}.defaultDecision`);
    }

    if (policy.uiResponsibility !== 'visibility-and-disabled-state-only') {
      missing.push(`${AUTHORIZATION_POLICY_KEY}.uiResponsibility`);
    }

    const actionGroups = policy.actionGroups || {};
    ['headerActions', 'rowActions', 'bulkActions', 'formActions', 'detailActions'].forEach((key) => {
      if (!(key in actionGroups)) {
        missing.push(`${AUTHORIZATION_POLICY_KEY}.actionGroups.${key}`);
      }
    });

    const targets = policy.providerTargets || {};
    ['headerActions', 'rowActions', 'bulkActions', 'formActions', 'detailActions', 'disabledReason'].forEach((key) => {
      if (!(key in targets)) {
        missing.push(`${AUTHORIZATION_POLICY_KEY}.providerTargets.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'authorization-policy-error';
  mount.dispatchEvent(new CustomEvent(AUTHORIZATION_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));

  return false;
}



function dispatchHydrationFailure(mount, schema, reason, missing) {
  mount.dispatchEvent(new CustomEvent(HYDRATION_FAILED_EVENT, {
    bubbles: true,
    detail: {
      reason,
      missing,
      resource: schema?.resource?.name || schema?.resource,
      operation: schema?.operation,
      surface: schema?.surface,
      provider: schema?.providers?.primary,
      hydration: mount.dataset[HYDRATION_ATTR] || 'unknown',
    },
  }));
}

function assertTelemetryPolicy(mount, schema) {
  const policy = schema?.[TELEMETRY_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(TELEMETRY_POLICY_KEY);
  } else {
    ['name', 'version', 'mode', 'owner', 'backendAuditOwner', 'piiPolicy', 'correlation', 'events', 'requiredDetailKeys', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${TELEMETRY_POLICY_KEY}.${key}`);
      }
    });

    if (policy.mode !== 'browser-ui-events') {
      missing.push(`${TELEMETRY_POLICY_KEY}.mode`);
    }

    if (policy.backendAuditOwner !== 'backend-audit-log') {
      missing.push(`${TELEMETRY_POLICY_KEY}.backendAuditOwner`);
    }

    if (policy.piiPolicy !== 'no-field-values-in-ui-events') {
      missing.push(`${TELEMETRY_POLICY_KEY}.piiPolicy`);
    }

    const events = policy.events || {};
    ['hydrationReady', 'hydrationFailed', 'providerRequiredError', 'actionIntent', 'actionDenied', 'viewModeChanged', 'contentLocaleChanged', 'selectionChanged', 'formDirtyStateChanged', 'formSubmitIntent'].forEach((key) => {
      if (!(key in events)) {
        missing.push(`${TELEMETRY_POLICY_KEY}.events.${key}`);
      }
    });

    ['resource', 'operation', 'surface', 'provider', 'hydration'].forEach((key) => {
      if (!Array.isArray(policy.requiredDetailKeys) || !policy.requiredDetailKeys.includes(key)) {
        missing.push(`${TELEMETRY_POLICY_KEY}.requiredDetailKeys.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'telemetry-policy-error';
  mount.dispatchEvent(new CustomEvent(TELEMETRY_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));
  dispatchHydrationFailure(mount, schema, 'telemetry-policy-error', missing);

  return false;
}

function assertAccessibilityPolicy(mount, schema) {
  const policy = schema?.[ACCESSIBILITY_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(ACCESSIBILITY_POLICY_KEY);
  } else {
    ['name', 'version', 'mode', 'owner', 'landmarks', 'keyboard', 'focus', 'announcements', 'labels', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${ACCESSIBILITY_POLICY_KEY}.${key}`);
      }
    });

    if (policy.mode !== 'provider-native-required') {
      missing.push(`${ACCESSIBILITY_POLICY_KEY}.mode`);
    }

    const landmarks = policy.landmarks || {};
    ['main', 'toolbar', 'table', 'form', 'detail'].forEach((key) => {
      if (!(key in landmarks)) {
        missing.push(`${ACCESSIBILITY_POLICY_KEY}.landmarks.${key}`);
      }
    });

    const targets = policy.providerTargets || {};
    ['page', 'toolbar', 'table', 'form', 'detail', 'liveRegion', 'focusManagement'].forEach((key) => {
      if (!(key in targets)) {
        missing.push(`${ACCESSIBILITY_POLICY_KEY}.providerTargets.${key}`);
      }
    });

    if (policy.keyboard?.required !== true) {
      missing.push(`${ACCESSIBILITY_POLICY_KEY}.keyboard.required`);
    }

    if (policy.focus?.restoreAfterAction !== true) {
      missing.push(`${ACCESSIBILITY_POLICY_KEY}.focus.restoreAfterAction`);
    }

    if (policy.announcements?.enabled !== true) {
      missing.push(`${ACCESSIBILITY_POLICY_KEY}.announcements.enabled`);
    }
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'accessibility-policy-error';
  mount.dispatchEvent(new CustomEvent(ACCESSIBILITY_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));
  dispatchHydrationFailure(mount, schema, 'accessibility-policy-error', missing);

  return false;
}



function assertResponsiveLayoutPolicy(mount, schema) {
  const policy = schema?.[RESPONSIVE_LAYOUT_POLICY_KEY];
  const missing = [];

  if (!policy || typeof policy !== 'object') {
    missing.push(RESPONSIVE_LAYOUT_POLICY_KEY);
  } else {
    ['name', 'version', 'mode', 'shellOwner', 'bodyOwner', 'breakpoints', 'density', 'table', 'cards', 'filters', 'forms', 'detail', 'providerTargets'].forEach((key) => {
      if (!(key in policy)) {
        missing.push(`${RESPONSIVE_LAYOUT_POLICY_KEY}.${key}`);
      }
    });

    if (policy.mode !== 'provider-native-responsive-layout') {
      missing.push(`${RESPONSIVE_LAYOUT_POLICY_KEY}.mode`);
    }

    if (policy.shellOwner !== 'ecosystem-shell') {
      missing.push(`${RESPONSIVE_LAYOUT_POLICY_KEY}.shellOwner`);
    }

    if (policy.bodyOwner !== 'ant-design-procomponents') {
      missing.push(`${RESPONSIVE_LAYOUT_POLICY_KEY}.bodyOwner`);
    }

    const breakpoints = policy.breakpoints || {};
    ['desktop', 'tablet', 'mobile'].forEach((key) => {
      if (!(key in breakpoints)) {
        missing.push(`${RESPONSIVE_LAYOUT_POLICY_KEY}.breakpoints.${key}`);
      }
    });

    if (!Array.isArray(policy.density?.allowed) || !policy.density.allowed.includes('small') || !policy.density.allowed.includes('middle') || !policy.density.allowed.includes('large')) {
      missing.push(`${RESPONSIVE_LAYOUT_POLICY_KEY}.density.allowed`);
    }

    const targets = policy.providerTargets || {};
    ['page', 'table', 'tableScroll', 'tableOptions', 'filters', 'form', 'cards', 'detail'].forEach((key) => {
      if (!(key in targets)) {
        missing.push(`${RESPONSIVE_LAYOUT_POLICY_KEY}.providerTargets.${key}`);
      }
    });
  }

  if (missing.length === 0) {
    return true;
  }

  mount.dataset[HYDRATION_ATTR] = 'responsive-layout-policy-error';
  mount.dispatchEvent(new CustomEvent(RESPONSIVE_LAYOUT_POLICY_ERROR_EVENT, {
    bubbles: true,
    detail: { missing, schema },
  }));
  dispatchHydrationFailure(mount, schema, 'responsive-layout-policy-error', missing);

  return false;
}

function readProviderPolicy(schema) {
  return schema?.providerPolicy || {
    primary: {
      provider: schema?.providers?.primary || PRIMARY_ADMIN_PROVIDER,
      role: 'admin-workbench',
      required: true,
      expectedForSurface: 'admin',
    },
    secondary: {
      provider: schema?.providers?.secondary || SECONDARY_RICH_FACADE_PROVIDER,
      role: 'rich-facade',
      replacementMode: FORBIDDEN_SECONDARY_REPLACEMENT,
      mayReplacePrimary: false,
    },
  };
}

function assertProviderPolicy(mount, schema, policy) {
  const primaryProvider = policy?.primary?.provider || schema?.providers?.primary || PRIMARY_ADMIN_PROVIDER;
  const secondaryProvider = policy?.secondary?.provider || schema?.providers?.secondary || SECONDARY_RICH_FACADE_PROVIDER;
  const secondaryReplacementMode = policy?.secondary?.replacementMode || FORBIDDEN_SECONDARY_REPLACEMENT;
  const secondaryMayReplacePrimary = policy?.secondary?.mayReplacePrimary === true;

  if (schema?.surface === 'admin' && primaryProvider !== PRIMARY_ADMIN_PROVIDER) {
    mount.dataset[HYDRATION_ATTR] = 'provider-policy-error';
    mount.dispatchEvent(new CustomEvent(PROVIDER_POLICY_ERROR_EVENT, {
      bubbles: true,
      detail: {
        reason: 'admin-body-primary-provider-must-be-antd-pro',
        primaryProvider,
        expectedProvider: PRIMARY_ADMIN_PROVIDER,
        schema,
      },
    }));

    return null;
  }

  if (secondaryProvider === PRIMARY_ADMIN_PROVIDER) {
    mount.dataset[HYDRATION_ATTR] = 'provider-policy-error';
    mount.dispatchEvent(new CustomEvent(PROVIDER_POLICY_ERROR_EVENT, {
      bubbles: true,
      detail: {
        reason: 'secondary-provider-must-not-duplicate-primary-admin-provider',
        secondaryProvider,
        schema,
      },
    }));

    return null;
  }

  if (secondaryMayReplacePrimary || secondaryReplacementMode !== FORBIDDEN_SECONDARY_REPLACEMENT) {
    mount.dataset[HYDRATION_ATTR] = 'provider-policy-error';
    mount.dispatchEvent(new CustomEvent(PROVIDER_POLICY_ERROR_EVENT, {
      bubbles: true,
      detail: {
        reason: 'secondary-provider-replacement-forbidden-for-admin-body',
        secondaryProvider,
        secondaryReplacementMode,
        schema,
      },
    }));

    return null;
  }

  return { providerName: primaryProvider, secondaryProvider, secondaryReplacementMode };
}

function resolveProvider(mount, schema) {
  const policy = readProviderPolicy(schema);
  const providerSelection = assertProviderPolicy(mount, schema, policy);

  if (!providerSelection) {
    return { providerName: null, provider: null, policy };
  }

  return {
    providerName: providerSelection.providerName,
    provider: providerFromRegistry(providerSelection.providerName),
    policy,
  };
}

function hydrateMount(mount) {
  const schema = parseSchema(mount);

  if (!schema) {
    return;
  }

  if (!assertSchemaManifest(mount, schema)) {
    return;
  }

  if (!assertResourceContract(mount, schema)) {
    return;
  }

  if (!assertOperationPolicy(mount, schema)) {
    return;
  }

  if (!assertToolbarPolicy(mount, schema)) {
    return;
  }

  if (!assertRowSelectionPolicy(mount, schema)) {
    return;
  }

  if (!assertTableInteractionPolicy(mount, schema)) {
    return;
  }

  if (!assertEmptyStatePolicy(mount, schema)) {
    return;
  }

  if (!assertFormLifecyclePolicy(mount, schema)) {
    return;
  }

  if (!assertDetailViewPolicy(mount, schema)) {
    return;
  }

  if (!assertNavigationPolicy(mount, schema)) {
    return;
  }

  if (!assertAuthorizationPolicy(mount, schema)) {
    return;
  }

  if (!assertTelemetryPolicy(mount, schema)) {
    return;
  }

  if (!assertAccessibilityPolicy(mount, schema)) {
    return;
  }

  if (!assertResponsiveLayoutPolicy(mount, schema)) {
    return;
  }

  const { providerName, provider, policy } = resolveProvider(mount, schema);
  const detail = { mount, schema, providerName, policy };

  if (provider && typeof provider.mount === 'function') {
    provider.mount(mount, schema);
    mount.dataset[HYDRATION_ATTR] = 'ready';
    window.dispatchEvent(new CustomEvent(READY_EVENT, { detail }));
    mount.dispatchEvent(new CustomEvent(READY_EVENT, { bubbles: true, detail }));

    return;
  }

  mount.dataset[HYDRATION_ATTR] = providerName ? 'waiting-for-provider' : 'provider-policy-error';
  mount.dataset[PROVIDER_WAITING_ATTR] = providerName || 'provider-policy';

  const bootMarker = mount.querySelector(PROVIDER_BOOT_MARKER_SELECTOR);
  if (bootMarker) {
    bootMarker.textContent = providerName
      ? `Interfacing provider boot: waiting for ${providerName} renderer registration.`
      : 'Interfacing provider boot: provider policy could not be resolved.';
  }

  const retry = () => {
    const retryProvider = providerName ? providerFromRegistry(providerName) : null;
    if (!retryProvider || typeof retryProvider.mount !== 'function') {
      return;
    }

    window.removeEventListener('interfacing:admin-body:provider-registered', retry);
    window.removeEventListener('interfacing:admin-body:canonical-providers-ready', retry);
    hydrateMount(mount);
  };

  window.addEventListener('interfacing:admin-body:provider-registered', retry);
  window.addEventListener('interfacing:admin-body:canonical-providers-ready', retry);

  mount.dispatchEvent(new CustomEvent(PROVIDER_REQUIRED_ERROR_EVENT, {
    bubbles: true,
    detail: {
      providerName,
      secondaryReplacement: FORBIDDEN_SECONDARY_REPLACEMENT,
      schema,
    },
  }));
  dispatchHydrationFailure(mount, schema, 'waiting-for-provider', [providerName || 'provider-policy']);
}

function boot() {
  document.querySelectorAll(MOUNT_SELECTOR).forEach(hydrateMount);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
  boot();
}

export { READY_EVENT, MOUNT_SELECTOR, SCHEMA_SELECTOR, PROVIDER_REGISTRY, PROVIDER_REGISTRY_API, SCHEMA_MANIFEST_KEY, RESOURCE_CONTRACT_KEY, OPERATION_POLICY_KEY, TOOLBAR_POLICY_KEY, ROW_SELECTION_POLICY_KEY, TABLE_INTERACTION_POLICY_KEY, EMPTY_STATE_POLICY_KEY, FORM_LIFECYCLE_POLICY_KEY, DETAIL_VIEW_POLICY_KEY, NAVIGATION_POLICY_KEY, AUTHORIZATION_POLICY_KEY, TELEMETRY_POLICY_KEY, ACCESSIBILITY_POLICY_KEY, RESPONSIVE_LAYOUT_POLICY_KEY };
