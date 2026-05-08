// Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp

const PROVIDER_NAME = 'primereact';
const REGISTRY_API_NAME = 'InterfacingAdminBodyProviderRegistry';
const EXTERNAL_PROVIDER_NAME = 'InterfacingPrimeReactAdminBodyProvider';
const PROVIDER_MISSING_EVENT = 'interfacing:admin-body:primereact-provider-missing';
const PROVIDER_REGISTERED_EVENT = 'interfacing:admin-body:primereact-provider-registered';

function dispatchProviderEvent(eventName, detail) {
  window.dispatchEvent(new CustomEvent(eventName, { detail }));
}

function attachPrimeReactProvider() {
  const registryApi = window[REGISTRY_API_NAME];
  const provider = window[EXTERNAL_PROVIDER_NAME];

  if (!registryApi || typeof registryApi.register !== 'function') {
    dispatchProviderEvent(PROVIDER_MISSING_EVENT, {
      provider: PROVIDER_NAME,
      reason: 'registry-api-missing',
    });
    return;
  }

  if (!provider || typeof provider.mount !== 'function') {
    dispatchProviderEvent(PROVIDER_MISSING_EVENT, {
      provider: PROVIDER_NAME,
      reason: 'external-provider-missing',
      expectedGlobal: EXTERNAL_PROVIDER_NAME,
    });
    return;
  }

  registryApi.register(PROVIDER_NAME, provider);
  dispatchProviderEvent(PROVIDER_REGISTERED_EVENT, {
    provider: PROVIDER_NAME,
    externalProvider: EXTERNAL_PROVIDER_NAME,
  });
}

attachPrimeReactProvider();

window.addEventListener('interfacing:admin-body:canonical-providers-ready', () => {
  attachPrimeReactProvider();
});

