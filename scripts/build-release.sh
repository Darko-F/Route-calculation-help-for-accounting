#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
project_root="$(cd "${script_dir}/.." && pwd)"

module_dir="${project_root}/transport-accounting"
component_dir="${project_root}/administrator-component-transport-accounting"
plugin_dir="${project_root}/transport-accounting-update-key-plugin"
package_dir="${project_root}/transport-accounting-package"
downloads_dir="${project_root}/downloads"

manifest_version() {
  sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' "$1" | head -n 1
}

module_version="$(manifest_version "${module_dir}/mod_transport_accounting.xml")"
component_version="$(manifest_version "${component_dir}/transport_accounting.xml")"
plugin_version="$(manifest_version "${plugin_dir}/transportaccountingupdatekey.xml")"
suite_version="$(manifest_version "${package_dir}/pkg_transport_accounting.xml")"

for version in "$module_version" "$component_version" "$plugin_version" "$suite_version"; do
  if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Invalid manifest version: ${version}" >&2
    exit 1
  fi
done

module_name="mod_transport_accounting_v${module_version}.zip"
component_name="com_transport_accounting_v${component_version}.zip"
plugin_name="plg_installer_transportaccountingupdatekey_v${plugin_version}.zip"
suite_name="pkg_transport_accounting_v${suite_version}.zip"

for package_ref in "$module_name" "$component_name" "$plugin_name"; do
  if ! grep -Fq ">${package_ref}<" "${package_dir}/pkg_transport_accounting.xml"; then
    echo "Package manifest does not reference ${package_ref}" >&2
    exit 1
  fi
done

mkdir -p "${package_dir}/packages" "$downloads_dir"

module_zip="${package_dir}/packages/${module_name}"
component_zip="${package_dir}/packages/${component_name}"
plugin_zip="${package_dir}/packages/${plugin_name}"
suite_zip="${downloads_dir}/${suite_name}"

rm -f -- "$module_zip" "$component_zip" "$plugin_zip" "$suite_zip"

(cd "$module_dir" && zip -q -r "$module_zip" . -x 'documentation.html')
(cd "$component_dir" && zip -q -r "$component_zip" .)
(cd "$plugin_dir" && zip -q -r "$plugin_zip" .)

cp "$module_zip" "${downloads_dir}/${module_name}"
cp "$component_zip" "${downloads_dir}/${component_name}"
cp "$plugin_zip" "${downloads_dir}/${plugin_name}"

(cd "$package_dir" && zip -q -r "$suite_zip" \
  pkg_transport_accounting.xml \
  language \
  "packages/${module_name}" \
  "packages/${component_name}" \
  "packages/${plugin_name}")

suite_sha256="$(sha256sum "$suite_zip" | cut -d' ' -f1)"
sed -i -E "s:file=pkg_transport_accounting_v[0-9]+\.[0-9]+\.[0-9]+\.zip:file=${suite_name}:" \
  "${project_root}/updates/transport-accounting-package.xml"
sed -i -E "s:<sha256>[a-fA-F0-9]+</sha256>:<sha256>${suite_sha256}</sha256>:" \
  "${project_root}/updates/transport-accounting-package.xml"

release_ignore_rule="!downloads/${suite_name}"
if ! grep -Fxq "$release_ignore_rule" "${project_root}/.gitignore"; then
  printf '\n%s\n' "$release_ignore_rule" >> "${project_root}/.gitignore"
fi

unzip -tqq "$module_zip"
unzip -tqq "$component_zip"
unzip -tqq "$plugin_zip"
unzip -tqq "$suite_zip"

echo "Built suite ${suite_version}: ${suite_zip}"
echo "Tracked updater artifact: ${suite_zip}"
echo "SHA-256: ${suite_sha256}"
