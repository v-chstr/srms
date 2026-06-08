# File Storage Rules

> Rules for file uploads, downloads, and Cloudflare R2 integration.
> Read this before any file upload/download work.

---

## Storage Architecture

| Environment | Disk | Driver | Config |
|---|---|---|---|
| Local dev (WAMP) | `local` | `local` | `FILESYSTEM_DISK=local` in `.env` |
| Production (Laravel Cloud) | `r2` | `s3` | `FILESYSTEM_DISK=r2` in `.env` |

The app uses `config('filesystems.default')` to determine which disk to use. Never hardcode disk names in controllers or services.

---

## Rule 1: Always Use Storage Facade

```php
// CORRECT — Storage abstraction
$path = $file->store('research/manuscripts', config('filesystems.default'));
Storage::disk($disk)->download($paper->file_path);

// WRONG — raw PHP file functions
file_put_contents(public_path('uploads/' . $filename), $content);
move_uploaded_file($tmp, $destination);
```

---

## Rule 2: Store Relative Paths in Database

The `file_path` column in `research_papers` stores a relative path like `research/manuscripts/abc123.pdf`.

**Never store:**
- Full URLs (`https://r2.cloudflare.com/...`)
- Absolute paths (`C:\wamp64\...`)
- Public URLs

The relative path works with any disk — `Storage::disk('local')->download($path)` and `Storage::disk('r2')->temporaryUrl($path, ...)` both accept the same relative path.

---

## Rule 3: Upload Pattern

```php
// In ResearchService.php
public function store(array $validated, User $submitter): ResearchPaper
{
    $disk = config('filesystems.default');
    $path = $validated['manuscript']->store('research/manuscripts', $disk);

    return ResearchPaper::create([
        'title'        => $validated['title'],
        'abstract'     => $validated['abstract'],
        'file_path'    => $path,
        'course_id'    => $submitter->course_id,
        'submitted_by' => $submitter->id,
        'status'       => 'pending',
    ]);
}
```

---

## Rule 4: Download Pattern (Dual-Disk)

```php
// In a download controller method
public function download(ResearchPaper $paper)
{
    $this->authorize('download', $paper);

    $disk = config('filesystems.default');

    if ($disk === 'r2') {
        // R2 supports temporary signed URLs
        $url = Storage::disk('r2')->temporaryUrl(
            $paper->file_path,
            now()->addMinutes(30)
        );
        return redirect($url);
    }

    // Local disk — serve directly through Laravel
    return Storage::disk('local')->download($paper->file_path);
}
```

**Critical:** `temporaryUrl()` only works on S3-compatible disks (R2). Calling it on the `local` disk throws `RuntimeException`. Always check which disk is active.

---

## Rule 5: Validate Uploads in FormRequest

```php
// In StoreResearchRequest.php
public function rules(): array
{
    return [
        'title'       => ['required', 'string', 'max:255'],
        'abstract'    => ['required', 'string', 'max:5000'],
        'manuscript'  => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB max
    ];
}
```

Always validate:
- File type (`mimes:pdf`)
- File size (`max:10240` = 10MB)
- That the field is actually a file (`'file'` rule)

---

## Rule 6: Resubmission (File Replacement)

When a student resubmits after revision, delete the old file before storing the new one:

```php
public function update(array $validated, ResearchPaper $paper): ResearchPaper
{
    $disk = config('filesystems.default');

    if (isset($validated['manuscript'])) {
        // Delete old file
        Storage::disk($disk)->delete($paper->file_path);
        // Store new file
        $validated['file_path'] = $validated['manuscript']->store('research/manuscripts', $disk);
    }

    $paper->update($validated);
    return $paper;
}
```

---

## Rule 7: R2 Configuration

The `r2` disk is defined in `config/filesystems.php`:

```php
'r2' => [
    'driver'                  => 's3',
    'key'                     => env('AWS_ACCESS_KEY_ID'),
    'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
    'region'                  => env('AWS_DEFAULT_REGION', 'auto'),
    'bucket'                  => env('AWS_BUCKET', 'srms-dev'),
    'endpoint'                => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
    'throw'                   => true,
    'visibility'              => 'private',
],
```

Credentials are set in `.env` — see `docs/dev-plan-main.md § 1.6` for the R2 API key setup guide.

---

## Rule 8: Never Expose Storage URLs Directly

Research papers are private. Downloads must always go through an authenticated controller route:

```
GET /student/research/{id}/download  → auth + role:student
GET /adviser/reviews/{paper}/download → auth + role:adviser
GET /admin/papers/{paper}/download   → auth + role:admin
```

Never make the storage bucket public. Never generate permanent URLs.
