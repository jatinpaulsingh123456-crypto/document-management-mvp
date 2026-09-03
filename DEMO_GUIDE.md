# MVP Demo Guide

## Goal

The demo should prove the requirements that are easiest for an evaluator to verify:

1. The application runs.
2. An employee can upload and manage an allowed file.
3. Metadata is recorded.
4. Folder navigation works.
5. A restricted resource is denied to an employee.
6. A CEO/MD/appropriate head can access a broader organizational scope.

The demo should take about 5–10 minutes.

---

## Before the Demo

Start Docker:

```bash
cd /Users/jatinpaulsingh/document-management-mvp
docker compose up -d
```

Check services:

```bash
docker compose ps
```

Check the API:

```bash
curl http://localhost:8080/api/v1/health
```

Open:

```text
http://localhost:8080
```

Make sure you have two working test accounts:

```text
EMPLOYEE
MANAGEMENT (CEO / MD / Country Head)
```

Use your existing working credentials. Do not put real passwords in the README or GitHub.

---

# Part 1 — Employee Demo

### Step 1 — Login

Log in with the Employee test account.

Show:

```text
Employee name
Employee role
```

### Step 2 — Open My Files / File Browser

Show the employee's permitted folder.

Point out the folder navigation/breadcrumb.

### Step 3 — Upload

Upload a sample file such as:

```text
employee-demo.pdf
```

A good demo file is small and harmless.

After upload, show the file row.

Point out:

```text
Name
Type
Uploader
Size
Date
Location
```

### Step 4 — Show Metadata

Open/view the uploaded file or its row and demonstrate that metadata is persisted.

Mention:

> "The file record contains the uploader, upload time, type, size, folder and organization metadata."

### Step 5 — Rename

Rename:

```text
employee-demo.pdf
```

to:

```text
employee-demo-renamed.pdf
```

### Step 6 — Move

Move the file to an allowed folder.

Then navigate to that folder and show the file is there.

### Step 7 — Download

Download the file and confirm it opens correctly.

### Step 8 — Permission Test

This is the most important part.

Try to access a file or folder belonging to another employee that is outside the current employee's allowed scope.

The expected API response is:

```text
403 Forbidden
```

Explain:

> "The backend enforces authorization; hiding a button in the UI is not the security mechanism."

If you have a browser or API console available, show the denied response.

---

# Part 2 — CEO / MD / Country Head Demo

Log out and log in with the management test account.

### Step 1 — Show broader folder visibility

Open the file browser.

Navigate through:

```text
Location
   ↓
Department
   ↓
User
```

Show that the management role has broader visibility than the employee.

### Step 2 — Find the employee file

Locate:

```text
employee-demo-renamed.pdf
```

Show that management can access it.

### Step 3 — Download

Download the same file.

### Step 4 — Management Action

Perform one management action allowed by the role, such as:

```text
Move
Rename
Delete
```

Use only an action that your current implementation permits for that account.

---

# Part 3 — Security Proof

If there is enough time, demonstrate the contrast:

```text
Employee
    |
    +--> Own file       = ALLOWED
    |
    +--> Restricted file = 403

Management
    |
    +--> Broader organization scope = ALLOWED
```

This is more valuable than spending the demo showing cosmetic UI features.

---

# Suggested Spoken Demo

Use this simple explanation:

> "This is the self-hosted document and media management MVP. Files are stored with metadata and organized using an organizational folder hierarchy. I will first demonstrate the Employee role, then a management role to show the access difference."

Employee:

> "The employee can upload a file into their permitted scope. The system stores the file metadata and supports navigation, download and allowed file actions."

Security:

> "The important part is that authorization is enforced by the API. If this employee attempts to access a restricted resource, the backend returns 403 rather than relying only on the UI."

Management:

> "Now I am logged in as management. The same application exposes a broader organizational scope, allowing the manager to browse locations, departments and employee files according to the configured role."

Finish:

> "The MVP intentionally leaves document collaboration, version history, OCR/full-text search and a native mobile application for future work."

---

# Demo Evidence to Capture

Take screenshots of:

1. Employee login/dashboard
2. Employee file browser
3. Successful file upload
4. File metadata
5. Folder navigation
6. Employee forbidden access / 403
7. Management login
8. Management broader folder view
9. Management accessing the employee file

These screenshots are enough to support a short recorded or live demo.

---

# Important

Do not create a fake 403 screenshot.

Do not claim a role has access to a scope unless the actual API returns the resource.

Do not use real company documents in the demonstration. Use test files.
