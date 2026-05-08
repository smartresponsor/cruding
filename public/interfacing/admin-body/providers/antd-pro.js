// Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
// Ant Design ProComponents admin body provider attachment point.
//
// This module does not implement a React/AntD renderer. The real renderer is
// expected to publish `window.InterfacingAntDesignProAdminBodyProvider` with
// a mount(mount, schema) function. This adapter only registers that renderer
// with the Interfacing provider registry when it exists.

const PROVIDER_NAME = 'antd-pro';
const REGISTRY_API_NAME = 'InterfacingAdminBodyProviderRegistry';
const EXTERNAL_PROVIDER_NAME = 'InterfacingAntDesignProAdminBodyProvider';
const PROVIDER_MISSING_EVENT = 'interfacing:admin-body:antd-pro-provider-missing';
const PROVIDER_REGISTERED_EVENT = 'interfacing:admin-body:antd-pro-provider-registered';

function getRegistryApi() {
  return window[REGISTRY_API_NAME] || null;
}

function getExternalProvider() {
  return window[EXTERNAL_PROVIDER_NAME] || null;
}

function isMountableProvider(provider) {
  return Boolean(provider && typeof provider === 'object' && typeof provider.mount === 'function');
}

function dispatchProviderEvent(eventName, detail) {
  window.dispatchEvent(new CustomEvent(eventName, { detail }));
}

function attachAntDesignProProvider() {
  const registryApi = getRegistryApi();
  const provider = getExternalProvider();

  if (!registryApi || typeof registryApi.register !== 'function') {
    dispatchProviderEvent(PROVIDER_MISSING_EVENT, {
      providerName: PROVIDER_NAME,
      reason: 'provider-registry-missing',
    });

    return false;
  }

  if (!isMountableProvider(provider)) {
    dispatchProviderEvent(PROVIDER_MISSING_EVENT, {
      providerName: PROVIDER_NAME,
      externalProviderName: EXTERNAL_PROVIDER_NAME,
      reason: 'external-provider-missing',
    });

    return false;
  }

  registryApi.register(PROVIDER_NAME, provider);
  dispatchProviderEvent(PROVIDER_REGISTERED_EVENT, {
    providerName: PROVIDER_NAME,
    externalProviderName: EXTERNAL_PROVIDER_NAME,
    provider,
  });

  return true;
}

attachAntDesignProProvider();

window.addEventListener('interfacing:admin-body:canonical-providers-ready', () => {
  attachAntDesignProProvider();
});


export {
  PROVIDER_NAME,
  REGISTRY_API_NAME,
  EXTERNAL_PROVIDER_NAME,
  PROVIDER_MISSING_EVENT,
  PROVIDER_REGISTERED_EVENT,
  attachAntDesignProProvider,
};
