/**
 * Compares two version strings (e.g., "1.2.3").
 * @param {string} v1 The first version string.
 * @param {string} v2 The second version string.
 * @returns {number} 1 if v1 > v2, -1 if v1 < v2, 0 if v1 === v2.
 */
function compareVersions(v1, v2) {
    const v1Parts = v1.split('.').map(part => parseInt(part, 10));
    const v2Parts = v2.split('.').map(part => parseInt(part, 10));
    const len = Math.max(v1Parts.length, v2Parts.length);

    for (let i = 0; i < len; i += 1) {
        const p1 = v1Parts[i] || 0;
        const p2 = v2Parts[i] || 0;

        if (p1 > p2) {
            return 1;
        }
        if (p1 < p2) {
            return -1;
        }
    }

    return 0;
}

/**
 * Cleans a version string by removing prefixes and suffixes (e.g., "6.5.0.0-RC1" becomes "6.5.0.0").
 * @param {string} version The version string to clean.
 * @returns {string} The cleaned version string.
 */
function cleanVersionString(version) {
    let cleanedVersion = version.startsWith('v') ? version.substring(1) : version;
    [cleanedVersion] = cleanedVersion.split('-');
    return cleanedVersion;
}

const Feature = {
    isActive(version) {
        const currentVersion = Shopware.Context.app.config.version;
        const cleanedCurrentVersion = cleanVersionString(currentVersion);
        const cleanedCheckVersion = cleanVersionString(version);

        return compareVersions(cleanedCurrentVersion, cleanedCheckVersion) >= 0;
    },
};

export default Feature;