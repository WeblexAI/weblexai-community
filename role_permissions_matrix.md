# Comprehensive Role Permissions Analysis

## Overview
This document outlines the state of role-based permissions across the Weblex Open Source codebase. It identifies verified access control mechanisms, exposes remaining gaps, and acts as the project's standard reference.

## Current State Analysis

### 🛡️ Verified Protected Actions
The codebase implements authorization gates (via Laravel Policies and Form Requests) for all administrative and modifications actions:

| Controller | Action | Route Name | Current Access | Policy / Middleware |
|---|---|---|---|---|
| `ProjectController` | **Update Project Settings** | `projects.update` | ✅ **Manager+** | `ProjectPolicy::update` |
| `ProjectLanguageController` | **Attach Language** | `languages.attach` | ✅ **Manager+** | `LanguagePolicy::manage` |
| `ProjectLanguageController` | **Detach Language** | `languages.delete` | ✅ **Manager+** | `LanguagePolicy::manage` |
| `ProjectLanguageController` | **Enable/Disable Language** | `languages.enable/disable` | ✅ **Manager+** | `LanguagePolicy::manage` |
| `ProjectLanguageController` | **Toggle Publicity** | `languages.toggle-publicity` | ✅ **Manager+** | `LanguagePolicy::manage` |
| `ProjectLanguageController` | **Toggle Automatics** | `languages.toggle-automatics` | ✅ **Manager+** | `LanguagePolicy::manage` |
| `LanguageSwitcherController` | **Update Switcher Config** | `language-switcher.update` | ✅ **Manager+** | `LanguageSwitcherConfigPolicy::update` |
| `GlossaryController` | **Create Glossary** | `glossaries.create` | ✅ **Translator+** | `GlossaryPolicy::manage` |
| `GlossaryController` | **Update Glossary** | `glossaries.update` | ✅ **Translator+** | `GlossaryPolicy::manage` |
| `GlossaryController` | **Delete Glossary** | `glossaries.delete` | ✅ **Translator+** | `GlossaryPolicy::delete` |
| `PageController` | **Toggle Blacklist** | `pages.blacklist` | ✅ **Manager+** | `PagePolicy::toggleBlacklist` |
| `TranslationController` | **Update Translated Content** | `translations.translated` | ✅ **Translator+** | `TranslationPolicy::update` |
| `TranslationController` | **Update Review Status** | `translations.review` | ✅ **Translator+** | `TranslationPolicy::update` |
| `TranslationController` | **Update Visibility** | `translations.visibility` | ✅ **Translator+** | `TranslationPolicy::update` |
| `ExcludedBlockController` | **Create/Update/Delete Block** | `excluded-blocks.*` | ✅ **Translator+** | `ExcludedBlockPolicy` |
| `TranslationModelController` | **Update Model Settings** | `translation-model.update` | ✅ **Manager+** | `ProjectPolicy::update` |
| `ActivityLogController` | **View Activity Logs** | `projects.activity-logs` | ✅ **Manager+** | `ProjectPolicy::update` (via index & Overview api) |

### 🚨 Remaining Gaps & Discrepancies
Below are the confirmed gaps and differences between the code implementation and standard security models:

*   **Setup Guide Page:** The route `projects.setup` (`ProjectSetupController@index`) is restricted to **Manager+** in the codebase. This is the intended behavior (restricted to Managers and Owners).
*   **Collaborators Management:** There is no front-end `CollaboratorsController` or `collaborators.invite` route. Management of project members is handled entirely in Filament by users with the platform role `admin`.
*   **Billing/Plans:** Feature does not exist in this Open Source edition (Not Implemented / Not Needed).

---

## Implemented Permissions Matrix

**Legend:**
- 🛡️ **Owner**: Project Creator (Full Access)
- 💼 **Manager**: Can manage settings and options, but cannot perform platform-level actions like deleting the project or assigning admins.
- ✍️ **Translator**: Can edit translations, glossary terms, and excluded blocks. No settings access.
- 👁️ **Viewer**: Read-only access to translations, glossary, pages, and basic dashboard metrics.

| Feature Area | Action | 🛡️ Owner | 💼 Manager | ✍️ Translator | 👁️ Viewer |
| :--- | :--- | :---: | :---: | :---: | :---: |
| **Project Settings** | Delete Project | ❌ (Admin-only) | ❌ | ❌ | ❌ |
| | Update General Settings (Name/Domain) | ✅ | ✅ | ❌ | ❌ |
| | View Setup Guide | ✅ | ✅ | ❌ (403 Code) | ❌ (403 Code) |
| **Languages** | Attach/Detach Languages | ✅ | ✅ | ❌ | ❌ |
| | Enable/Disable Languages | ✅ | ✅ | ❌ | ❌ |
| | Toggle Public/Automatic Visibility | ✅ | ✅ | ❌ | ❌ |
| **Pages** | Toggle Blacklist status | ✅ | ✅ | ❌ | ❌ |
| | View Pages List | ✅ | ✅ | ✅ | ✅ |
| **Translations** | Edit Translated Text | ✅ | ✅ | ✅ | ❌ |
| | Mark as Reviewed | ✅ | ✅ | ✅ | ❌ |
| | Toggle Visibility | ✅ | ✅ | ✅ | ❌ |
| | Import/Export Translations | ✅ | ✅ | ✅ | ❌ |
| **Glossary** | Create/Edit/Delete Terms | ✅ | ✅ | ✅ | ❌ |
| **Excluded Blocks** | Create/Edit/Delete Exclusions | ✅ | ✅ | ✅ | ❌ |
| **Visual Editor** | Update Language Switcher Config | ✅ | ✅ | ❌ | ❌ |
| **Translation Model** | Change Tone/Audience Settings | ✅ | ✅ | ❌ | ❌ |
| **Analytics** | View Page Views | ✅ | ✅ | ✅ | ✅ |
| | View Translation Requests | ✅ | ✅ | ✅ | ✅ |
| **Collaborators** | Invite / Remove Members | ❌ (Admin-only) | ❌ (Admin-only) | ❌ | ❌ |
| | View Team List | ✅ | ✅ | ✅ | ✅ |
| **Billing** | View Plans & Subscriptions | 🚫 N/A | 🚫 N/A | 🚫 N/A | 🚫 N/A |
| **Logs** | View Activity Logs | ✅ | ✅ | ❌ | ❌ |
