<div class="card mt-6 max-w-2xl space-y-4 p-6">
    <div>
        <label for="name" class="mb-1 block text-sm font-medium">Game title</label>
        <input type="text" id="name" name="name" value="{{ old('name', $game->name ?? '') }}" required maxlength="150" class="input">
        @error('name')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="mb-1 block text-sm font-medium">Description</label>
        <textarea id="description" name="description" rows="5" required maxlength="5000" class="input">{{ old('description', $game->description ?? '') }}</textarea>
        @error('description')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="genre" class="mb-1 block text-sm font-medium">Genre</label>
            <select id="genre" name="genre" required class="input">
                @foreach ($genres as $genre)
                    <option value="{{ $genre }}" @selected(old('genre', $game->genre ?? '') === $genre)>{{ $genre }}</option>
                @endforeach
            </select>
            @error('genre')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="price" class="mb-1 block text-sm font-medium">Price (IDR)</label>
            <input type="number" id="price" name="price" value="{{ old('price', $game->price ?? '') }}" required min="0" max="999999999" step="0.01" class="input">
            @error('price')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="trailer_url" class="mb-1 block text-sm font-medium">Trailer URL <span class="text-faint">(optional)</span></label>
        <input type="url" id="trailer_url" name="trailer_url" value="{{ old('trailer_url', $game->trailer_url ?? '') }}" maxlength="255" class="input">
        @error('trailer_url')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="cover" class="mb-1 block text-sm font-medium">Cover image</label>
        @isset($game)
            <img src="{{ Storage::url($game->cover_path) }}" alt="Current cover" class="mb-2 h-28 w-48 rounded-xl object-cover">
        @endisset
        <input type="file" id="cover" name="cover" accept=".jpg,.jpeg,.png,.webp" @if(! isset($game)) required @endif class="input">
        @error('cover')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="screenshots" class="mb-1 block text-sm font-medium">Screenshots <span class="text-faint">(up to 5)</span></label>
        @isset($game)
            @if ($game->screenshots->isNotEmpty())
                <div class="mb-2 flex flex-wrap gap-2">
                    @foreach ($game->screenshots as $shot)
                        <img src="{{ Storage::url($shot->path) }}" alt="Screenshot" class="h-16 w-28 rounded-lg object-cover">
                    @endforeach
                </div>
            @endif
        @endisset
        <input type="file" id="screenshots" name="screenshots[]" accept="image/*" multiple class="input">
        @error('screenshots')<p class="field-error">{{ $message }}</p>@enderror
        @error('screenshots.*')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="file" class="mb-1 block text-sm font-medium">Installer file</label>
        <input type="file" id="file" name="file" accept=".zip,.7z,.rar,.gz" @if(! isset($game)) required @endif class="input">
        <p class="mt-1 text-sm text-faint">zip/7z/rar/gz, max 50 MB</p>
        @error('file')<p class="field-error">{{ $message }}</p>@enderror
    </div>
</div>
