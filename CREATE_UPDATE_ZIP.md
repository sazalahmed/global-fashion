# How to Create an Update Zip for the Server

When the user asks you (the AI) to "generate an update file", "make a zip for the server", or something similar, follow these exact steps without asking any questions.

### 1. Identify Modified Files
Check which files were modified in the current task/conversation. 
(You can use `git status -s` or simply recall the files you just edited).

### 2. Exclude Unwanted Files
Always exclude `.env`, `.git` directory, and any local testing files (like scratch scripts).

### 3. Generate the Zip using PHP (Preserving Directory Structure)
Do NOT use PowerShell's `Compress-Archive` because it does not preserve the directory structure when given a list of files, which ruins the cPanel upload.

Instead, write a quick PHP script to generate the zip file. You can use the following template:

```php
<?php
$zip = new ZipArchive();
if ($zip->open('update.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    
    // Replace this array with the actual files you modified
    $files = [
        'app/Models/User.php',
        'routes/web.php',
        // ... list all modified files here ...
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            // The second parameter $file ensures the directory structure is preserved inside the zip
            $zip->addFile($file, $file);
        }
    }
    $zip->close();
    echo "Zip created successfully!";
} else {
    echo "Failed to create zip.";
}
```

### 4. Execute and Provide the Link
1. Use the `write_to_file` tool to save this script as `create_zip.php` in the root directory.
2. Use the `run_command` tool to execute `php create_zip.php`.
3. Provide the user with the direct link to the `update.zip` file using the `file:///...` markdown syntax.
4. Clean up by deleting `create_zip.php` if desired.

By following this document, you will perfectly package the files with their exact folder structure for a drop-in server replacement!
