import re

with open('routes/web.php', 'r') as f:
    content = f.read()

# Route::get('/tanda-terima', ...);
content = re.sub(
    r"Route::get\('/tanda-terima',\s*function\s*\(Illuminate\\Http\\Request\s*\$request\)\s*\{.*?\}\);",
    r"Route::get('/tanda-terima', [\\App\\Http\\Controllers\\DocumentController::class, 'tandaTerima']);",
    content,
    flags=re.DOTALL
)

# Route::get('/tanda-terima/new', ...);
content = re.sub(
    r"Route::get\('/tanda-terima/new',\s*function\s*\(Illuminate\\Http\\Request\s*\$request\)\s*\{.*?\}\);",
    r"Route::get('/tanda-terima/new', [\\App\\Http\\Controllers\\DocumentController::class, 'tandaTerimaNew']);",
    content,
    flags=re.DOTALL
)

# Route::get('/schedule-tukar-faktur', ...);
content = re.sub(
    r"Route::get\('/schedule-tukar-faktur',\s*function\s*\(Illuminate\\Http\\Request\s*\$request\)\s*\{.*?\}\);",
    r"Route::get('/schedule-tukar-faktur', [\\App\\Http\\Controllers\\DocumentController::class, 'scheduleTukarFaktur']);",
    content,
    flags=re.DOTALL
)

# Route::get('/surat-tagihan', ...);
content = re.sub(
    r"Route::get\('/surat-tagihan',\s*function\s*\(Illuminate\\Http\\Request\s*\$request\)\s*\{.*?\}\);",
    r"Route::get('/surat-tagihan', [\\App\\Http\\Controllers\\DocumentController::class, 'suratTagihan']);",
    content,
    flags=re.DOTALL
)

# Route::get('/titip-internal', ...);
content = re.sub(
    r"Route::get\('/titip-internal',\s*function\s*\(Illuminate\\Http\\Request\s*\$request\)\s*\{.*?\}\);",
    r"Route::get('/titip-internal', [\\App\\Http\\Controllers\\DocumentController::class, 'titipInternal']);",
    content,
    flags=re.DOTALL
)

with open('routes/web.php', 'w') as f:
    f.write(content)

print("Done")
