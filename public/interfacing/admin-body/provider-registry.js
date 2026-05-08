// Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
// Interfacing admin body provider registry.
//
// This file defines the stable browser-side registration surface used by
// provider renderers. It does not implement Ant Design ProComponents or
// PrimeReact itself; those packages register concrete providers here.

const REGISTRY_NAME = 'InterfacingAdminBodyProviders';
const REGISTRY_API_NAME = 'InterfacingAdminBodyProviderRegistry';

function ensureProviderRegistry() {
  if (!window[REGISTRY_NAME] || typeof window[REGISTRY_NAME] !== 'object') {
    window[REGISTRY_NAME] = Object.create(null);
  }

  return window[REGISTRY_NAME];
}

function assertProviderName(providerName) {
  if (typeof providerName !== 'string' || providerName.trim() === '') {
    throw new TypeError('Interfacing admin body provider name must be a non-empty string.');
  }
}

function assertProvider(provider) {
  if (!provider || typeof provider !== 'object' || typeof provider.mount !== 'function') {
    throw new TypeError('Interfacing admin body provider must expose mount(mount, schema).');
  }
}

function registerProvider(providerName, provider) {
  assertProviderName(providerName);
  assertProvider(provider);

  const registry = ensureProviderRegistry();
  registry[providerName] = provider;

  window.dispatchEvent(new CustomEvent('interfacing:admin-body:provider-registered', {
    detail: { providerName, provider },
  }));

  return provider;
}

function hasProvider(providerName) {
  assertProviderName(providerName);

  return Object.prototype.hasOwnProperty.call(ensureProviderRegistry(), providerName);
}

function getProvider(providerName) {
  assertProviderName(providerName);

  return ensureProviderRegistry()[providerName] || null;
}

function listProviders() {
  return Object.keys(ensureProviderRegistry());
}

window[REGISTRY_API_NAME] = Object.freeze({
  register: registerProvider,
  has: hasProvider,
  get: getProvider,
  list: listProviders,
});

ensureProviderRegistry();

export {
  REGISTRY_NAME,
  REGISTRY_API_NAME,
  ensureProviderRegistry,
  registerProvider,
  hasProvider,
  getProvider,
  listProviders,
};
