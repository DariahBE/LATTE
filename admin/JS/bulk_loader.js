class CSVHandler {
    constructor(containerId, uploadEndpoint = 'ajax/bulk_text_insert.php') {
        this.container = document.getElementById(containerId);
        this.uploadEndpoint = uploadEndpoint;
        this.csvData = [];
        this.batchSize = 40; 
        this.file_content = null; 
        this.delimiter = ',';
        this.enclosure = '"';
        this.lineEnding = '\n';
        this.skipHeader = false;
        this.previewRows = 5;
        this.datamodel = {};
        this.progressContainer = null;
        this.headerNames = []; 
        this.initUI();
    }

    initUI() {
        this.container.innerHTML = `
            <div class="p-6 border border-slate-200 rounded-lg bg-slate-50 space-y-6">
                <div id="csv_settings" class="bg-white p-4 border border-slate-200 rounded-lg shadow-2xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="delimiter" class="block text-xs font-semibold text-slate-700 mb-1">Delimiter</label>
                            <input id="delimiter" type="text" value="," class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                        </div>
                        <div>
                            <label for="enclosure" class="block text-xs font-semibold text-slate-700 mb-1">Enclosed by</label>
                            <input id="enclosure" type="text" value='"' class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                        </div>
                        <div>
                            <label for="previewRows" class="block text-xs font-semibold text-slate-700 mb-1">Rows to preview</label>
                            <input id="previewRows" type="number" min="1" max="100" value="5" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="flex flex-col justify-end">
                            <label class="inline-flex items-center gap-2 cursor-pointer py-2">
                                <input id="hasheader" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-xs font-semibold text-slate-700">First row is header</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="border-dashed border-2 border-slate-300 hover:border-indigo-500 p-8 text-center cursor-pointer bg-white hover:bg-slate-100 rounded-lg transition" id="dropzone">
                    <i class="fas fa-file-csv text-3xl text-slate-400 mb-2 block"></i>
                    <p class="font-medium text-slate-700 text-sm">Drop CSV file here, or click to browse</p>
                    <p class="text-xs text-slate-500 mt-1">Supports standard CSV files with delimiter and text enclosure</p>
                    <input type="file" id="csv_file_input" class="hidden" accept=".csv,.txt,text/csv">
                </div>

                <div id="preview" class="hidden overflow-auto max-h-64 border border-slate-200 rounded-lg bg-white shadow-2xs"></div>

                <div id="mapping_action_container" class="flex flex-wrap items-center gap-3">
                    <button id="mapTrigger" type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-sitemap text-xs"></i>
                        <span>Make mapping</span>
                    </button>
                    <span id="map_help_text" class="text-xs text-slate-500">Select or drop a CSV file to configure column mapping.</span>
                </div>

                <div id="mapping" class="hidden"></div>

                <div id="do_upload" class="hidden flex flex-wrap items-center gap-3">
                    <button id="upload" type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-cloud-upload-alt text-xs"></i>
                        <span>Upload</span>
                    </button>
                    <span id="upload_help_text" class="text-xs text-slate-500">Review your mapping above and proceed when ready.</span>
                </div>

                <div id="progressContainer" class="hidden p-5 bg-white border border-slate-200 rounded-lg shadow-2xs space-y-3">
                    <div id="message_upload_progress" class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-700">
                            <span id="messagecontent_progress">Upload in progress</span>
                            <span id="floatcontent_progress" class="ml-1 text-slate-500"></span>
                        </p>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div id="progressBarAnimation" class="bg-indigo-600 h-3 rounded-full transition-all duration-200" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        `;

        this.addEventListeners();
    }

    addEventListeners() {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('csv_file_input');
        
        if (dropzone && fileInput) {
            dropzone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) {
                    this.readCSV(fileInput.files[0]);
                }
            });
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('border-indigo-500', 'bg-indigo-50/50');
            });
            dropzone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('border-indigo-500', 'bg-indigo-50/50');
            });
            dropzone.addEventListener('drop', (e) => {
                dropzone.classList.remove('border-indigo-500', 'bg-indigo-50/50');
                this.handleDrop(e);
            });
        }

        document.getElementById('delimiter').addEventListener('input', (e) => this.updateSettings(e));
        document.getElementById('enclosure').addEventListener('input', (e) => this.updateSettings(e));
        document.getElementById('hasheader').addEventListener('change', (e) => this.updateSettings(e));
        document.getElementById('previewRows').addEventListener('input', (e) => this.updateSettings(e));
        document.getElementById('mapTrigger').addEventListener('click', (e) => this.renderMappingUI(e)); 
        document.getElementById('upload').addEventListener('click', (e) => this.uploadData(e));
    }

    updateSettings() {
        this.delimiter = document.getElementById('delimiter').value;
        this.enclosure = document.getElementById('enclosure').value;
        this.skipHeader = document.getElementById('hasheader').checked; 
        this.previewRows = parseInt(document.getElementById('previewRows').value);
        if (this.csvData.length) {
            this.csvData = this.parseCSV(this.file_content, false);
            this.renderPreview();
        }
    }

    handleDrag(event) {
        event.preventDefault();
        event.stopPropagation(); 
    } 

    handleDrop(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!event.dataTransfer.files || !event.dataTransfer.files.length) return;
        const file = event.dataTransfer.files[0];
        const validMimeTypes = ['text/csv', 'application/csv', 'application/vnd.ms-excel', 'text/plain', ''];
        const isCsvExt = file.name.toLowerCase().endsWith('.csv') || file.name.toLowerCase().endsWith('.txt');
        if (file && (validMimeTypes.includes(file.type) || isCsvExt)) {
            this.readCSV(file);
        }
    }

    readCSV(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const content = e.target.result;
            this.file_content = content; 
            this.csvData = this.parseCSV(content, false);
            this.renderPreview();

            const mapBtn = document.getElementById('mapTrigger');
            if (mapBtn) {
                mapBtn.removeAttribute('disabled');
            }
            const helpText = document.getElementById('map_help_text');
            if (helpText) {
                const totalLines = content.split(this.lineEnding).length;
                helpText.textContent = `Loaded ${file.name} (~${totalLines} rows, ${(file.size / 1024).toFixed(1)} KB). Click "Make mapping" to proceed.`;
                helpText.className = 'text-xs font-medium text-emerald-700';
            }
        };
        reader.readAsText(file);
    }

    parseCSV(content, full = false) {
        const rows = content.split(this.lineEnding);
        const parsedRows = rows.map(row => this.parseRow(row));
        if (full) { return parsedRows; }
        return parsedRows.slice(0, this.previewRows);
    }

    parseRow(row) {
        const regex = new RegExp(
            `${this.enclosure}([^${this.enclosure}]+|${this.enclosure}${this.enclosure})*${this.enclosure}|[^${this.delimiter}]+`,
            'g'
        );
        const matches = row.match(regex);
        
        if (matches) {
            return matches.map(match => {
                return match.replace(new RegExp(`^${this.enclosure}|${this.enclosure}$`, 'g'), '')
                            .replace(new RegExp(`${this.enclosure}${this.enclosure}`, 'g'), this.enclosure);
            });
        }
        return [];
    }

    renderPreview() {
        const previewDiv = document.getElementById('preview');
        previewDiv.innerHTML = '';
        previewDiv.classList.remove('hidden');

        const table = document.createElement('table');
        table.className = 'min-w-full divide-y divide-slate-200 text-xs';
        this.headerNames = [];
        if (this.skipHeader) {
            this.csvData[0].forEach((cell) => {
                this.headerNames.push(cell);
            });
        } else {
            let i = 0; 
            while (i <= (this.csvData[0].length - 1)) {
                this.headerNames.push(`Column_${i}`);
                i++;
            }
        }

        const thead = document.createElement('thead');
        const tr = document.createElement('tr'); 
        tr.className = 'bg-slate-50';
        let j = 0; 
        this.headerNames.forEach((header) => { 
            const th = document.createElement('th'); 
            th.className = 'px-3 py-2 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider truncate max-w-xs border-r border-slate-200 last:border-r-0'; 
            th.textContent = header; 
            th.setAttribute('data-col', j);
            j++;
            tr.appendChild(th); 
        }); 
        thead.appendChild(tr);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        tbody.className = 'divide-y divide-slate-100 bg-white';
        this.csvData.forEach((row, rowIndex) => {
            if (this.skipHeader && rowIndex === 0) {
                return;
            }
            const rowTr = document.createElement('tr');
            rowTr.className = 'hover:bg-slate-50/75 transition-colors';
            row.forEach((cell) => {
                const td = document.createElement('td');
                td.className = 'px-3 py-2 text-xs text-slate-600 truncate max-w-xs border-r border-slate-100 last:border-r-0 font-mono';
                td.textContent = cell;
                rowTr.appendChild(td);
            });
            tbody.appendChild(rowTr);
        });
        table.appendChild(tbody);
        previewDiv.appendChild(table);
    }

    setDatamodel(datamodelObject) {
        this.datamodel = datamodelObject;
    }

    renderMappingUI() {
        const uploadBtn = document.getElementById('upload');
        if (uploadBtn) {
            uploadBtn.removeAttribute('disabled');
        }

        document.getElementById('dropzone').classList.add('hidden');
        document.getElementById('csv_settings').classList.add('hidden');
        const mappingActionContainer = document.getElementById('mapping_action_container');
        if (mappingActionContainer) {
            mappingActionContainer.classList.add('hidden');
        } else {
            document.getElementById('mapTrigger').classList.add('hidden');
        }

        const doUpload = document.getElementById('do_upload');
        if (doUpload) {
            doUpload.classList.remove('hidden');
        }

        const mappingDiv = document.getElementById('mapping');
        mappingDiv.classList.remove('hidden');
        mappingDiv.innerHTML = `
            <div class="p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="pb-3 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-sitemap text-indigo-600"></i>
                        <span>Map CSV columns to text properties</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Assign each entity property to a column from your imported CSV file.</p>
                </div>
                <div id="mapping_fields" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
            </div>
        `;
        if (!this.csvData.length) return;

        const fieldsContainer = document.getElementById('mapping_fields');
        const csvHeaders = this.headerNames;

        Object.entries(this.datamodel).forEach(([key, value]) => {
            const div = document.createElement('div');
            div.className = 'space-y-1.5';

            const label = document.createElement('label');
            label.className = 'block text-xs font-semibold text-slate-700';
            const isRequired = (value[2] === true || key.toLowerCase() === 'text');
            label.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${value[0]} <span class="text-slate-400 font-normal">(${value[1]})</span></span>
                    ${isRequired ? '<span class="text-[10px] font-semibold text-amber-600 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">Required</span>' : ''}
                </div>
            `;
            div.appendChild(label);

            const select = document.createElement('select');
            select.setAttribute('data-for-neocol', key); 
            select.className = 'w-full text-xs px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs';

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Choose a column of your CSV file';
            defaultOption.disabled = true;
            defaultOption.selected = true;
            select.appendChild(defaultOption);

            let j = 0; 
            csvHeaders.forEach((header) => {
                const option = document.createElement('option');
                option.value = header;
                option.setAttribute('data-col', j);
                j++;
                option.textContent = header;
                if (header.toLowerCase() === value[0].toLowerCase() || header.toLowerCase() === key.toLowerCase()) {
                    option.selected = true;
                    defaultOption.selected = false;
                }
                select.appendChild(option);
            });
            div.appendChild(select);
            fieldsContainer.appendChild(div);
        });
    }

    uploadData() {
        if (!this.uploadEndpoint) return;
        this.csvData = this.parseCSV(this.file_content, true);
        
        const mapBox = document.getElementById('mapping'); 
        let final_mapping = {}; 
        
        Array.from(mapBox.querySelectorAll('select')).forEach((select) => {
            let neocol = select.getAttribute('data-for-neocol'); 
            let selectedColumn = select.value;  
            let selectedOption = select.options[select.selectedIndex];
            let selectedIdx = selectedOption ? selectedOption.getAttribute('data-col') : null;
            if (selectedIdx !== null) {
                final_mapping[neocol] = [selectedColumn, parseInt(selectedIdx)];
            }
        }); 
        
        const csv_cols = Object.values(final_mapping).map(mapping => mapping[1]); 
        const keys = Object.keys(final_mapping);
        
        document.getElementById('progressContainer').classList.remove('hidden');
        document.getElementById('mapping').classList.add('hidden');
        document.getElementById('preview').classList.add('hidden');
        const mappingActionContainer = document.getElementById('mapping_action_container');
        if (mappingActionContainer) mappingActionContainer.classList.add('hidden');
        document.getElementById('mapTrigger').classList.add('hidden');
        const doUpload = document.getElementById('do_upload');
        if (doUpload) doUpload.classList.add('hidden');
        document.getElementById('upload').classList.add('hidden');
        
        let progress = 0;
        const totalRows = this.csvData.length;
                
        const uploadBatch = async (startIndex) => {
            if (startIndex >= totalRows) {
                document.getElementById('progressBarAnimation').style.width = '100%';
                document.getElementById('progressBarAnimation').classList.remove('bg-indigo-600');
                document.getElementById('progressBarAnimation').classList.add('bg-emerald-600');
                const msgEl = document.getElementById('messagecontent_progress');
                msgEl.textContent = 'Upload completed successfully!';
                msgEl.className = 'text-xs font-bold text-emerald-700';
                const floatEl = document.getElementById('floatcontent_progress');
                if (floatEl) floatEl.classList.add('hidden');
                return;
            }
            
            const endIndex = Math.min(startIndex + this.batchSize, totalRows);
            const batch = this.csvData.slice(startIndex, endIndex);
            
            let uploadDataBatch = batch.map(row => 
                csv_cols.filter(index => !isNaN(index) && index >= 0 && index < row.length)
                        .map(index => row[index])
            );
            
            const formData = new FormData();
            formData.append('keys', JSON.stringify(keys));
            formData.append('data', JSON.stringify(uploadDataBatch));
            if (window.csrfToken) {
                formData.append('csrf_token', window.csrfToken);
            }
    
            try {
                const response = await fetch(this.uploadEndpoint, {
                    method: 'POST',
                    body: formData
                });
                const jsonResponse = await response.json();
                if ('error' in jsonResponse) {
                    const msgEl = document.getElementById('messagecontent_progress');
                    msgEl.textContent = 'Upload failed: ' + (jsonResponse.msg || jsonResponse.error);
                    msgEl.className = 'text-xs font-semibold text-rose-600';
                    document.getElementById('progressBarAnimation').classList.remove('bg-indigo-600');
                    document.getElementById('progressBarAnimation').classList.add('bg-rose-500');
                    return;
                }
                
                progress += batch.length;
                uploadBatch(endIndex);
                this.update_progress_bar(progress, totalRows);
            } catch (error) {
                console.error('Error uploading batch:', error);
                const msgEl = document.getElementById('messagecontent_progress');
                if (msgEl) {
                    msgEl.textContent = 'An error occurred during upload.';
                    msgEl.className = 'text-xs font-semibold text-rose-600';
                }
            }
        };
        
        let rowstart = this.skipHeader ? 1 : 0;
        uploadBatch(rowstart);
    }

    update_progress_bar(progress, total) {
        let completionrate = progress / total; 
        let progressPercent = document.getElementById('floatcontent_progress');
        if (progressPercent) {
            progressPercent.textContent = `(${Math.floor(completionrate * 100)}%)`;
        }
        let progressBar = document.getElementById('progressBarAnimation');
        if (progressBar) {
            progressBar.style.width = `${completionrate * 100}%`;
        }
    }
}

class PreannotatedHandler {
    constructor(containerId, uploadEndpoint = 'ajax/bulk_preannotated_insert.php') {
        this.container = document.getElementById(containerId);
        this.uploadEndpoint = uploadEndpoint;
        this.selectedFile = null;
        this.format = 'tei';
        this.initUI();
    }

    initUI() {
        this.container.innerHTML = `
            <div class="p-6 border border-slate-200 rounded-lg bg-slate-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Corpus format</label>
                        <select id="preanno_format" class="border border-slate-300 rounded-lg px-3 py-2 w-full bg-white text-xs">
                            <option value="tei">TEI-XML (P5 with inline or stand-off markup)</option>
                            <option value="conllu">CoNLL-U / CoNLL-2003 (Token BIO tagging)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Document title (optional)</label>
                        <input id="preanno_title" type="text" placeholder="Defaults to file or TEI title" class="border border-slate-300 rounded-lg px-3 py-2 w-full bg-white text-xs">
                    </div>
                </div>

                <div class="border-dashed border-2 border-slate-300 p-8 text-center cursor-pointer bg-white hover:bg-slate-100 rounded-lg transition" id="preanno_dropzone">
                    <i class="fas fa-file-code text-3xl text-slate-400 mb-2 block"></i>
                    <p class="font-medium text-slate-700 text-sm">Drop pre-annotated XML, TEI, or CoNLL file here, or click to browse</p>
                    <p class="text-xs text-slate-500 mt-1">Supports .xml, .tei, .conll, .conllu, .txt</p>
                    <input type="file" id="preanno_file_input" class="hidden" accept=".xml,.tei,.conll,.conllu,.txt">
                </div>

                <div id="preanno_preview_box" class="mt-4 hidden p-4 bg-white border border-slate-200 rounded-lg">
                    <p class="text-xs font-semibold text-slate-700">Selected file: <span id="preanno_filename" class="font-normal text-slate-600"></span></p>
                    <p class="text-xs text-slate-500 mt-1">Size: <span id="preanno_filesize"></span></p>
                    <pre id="preanno_snippet" class="mt-2 text-xs bg-slate-100 p-2 rounded-lg max-h-40 overflow-auto font-mono text-slate-800"></pre>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button id="preanno_upload_btn" disabled class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-file-import text-xs"></i>
                        <span>Import pre-annotated text</span>
                    </button>
                    <span id="preanno_status" class="text-xs text-slate-600"></span>
                </div>
            </div>
        `;

        this.addEventListeners();
    }

    addEventListeners() {
        const dropzone = document.getElementById('preanno_dropzone');
        const fileInput = document.getElementById('preanno_file_input');
        const uploadBtn = document.getElementById('preanno_upload_btn');
        const formatSelect = document.getElementById('preanno_format');

        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('border-indigo-500', 'bg-indigo-50');
        });
        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('border-indigo-500', 'bg-indigo-50');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('border-indigo-500', 'bg-indigo-50');
            if (e.dataTransfer.files.length) {
                this.handleFile(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                this.handleFile(fileInput.files[0]);
            }
        });

        formatSelect.addEventListener('change', () => {
            this.format = formatSelect.value;
        });

        uploadBtn.addEventListener('click', () => this.upload());
    }

    handleFile(file) {
        this.selectedFile = file;
        const previewBox = document.getElementById('preanno_preview_box');
        const filenameSpan = document.getElementById('preanno_filename');
        const filesizeSpan = document.getElementById('preanno_filesize');
        const snippetPre = document.getElementById('preanno_snippet');
        const uploadBtn = document.getElementById('preanno_upload_btn');

        filenameSpan.textContent = file.name;
        filesizeSpan.textContent = (file.size / 1024).toFixed(1) + ' KB';

        const lowerName = file.name.toLowerCase();
        const formatSelect = document.getElementById('preanno_format');
        if (lowerName.endsWith('.conll') || lowerName.endsWith('.conllu')) {
            formatSelect.value = 'conllu';
            this.format = 'conllu';
        } else if (lowerName.endsWith('.xml') || lowerName.endsWith('.tei')) {
            formatSelect.value = 'tei';
            this.format = 'tei';
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            const text = e.target.result;
            snippetPre.textContent = text.slice(0, 1000) + (text.length > 1000 ? '\n... (truncated for preview)' : '');
            previewBox.classList.remove('hidden');
            uploadBtn.disabled = false;
        };
        reader.readAsText(file.slice(0, 5000));
    }

    async upload() {
        if (!this.selectedFile) return;

        const uploadBtn = document.getElementById('preanno_upload_btn');
        const statusSpan = document.getElementById('preanno_status');
        const titleInput = document.getElementById('preanno_title');

        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Importing...';
        statusSpan.className = 'text-xs text-slate-600';
        statusSpan.textContent = 'Parsing and writing to database...';

        const formData = new FormData();
        formData.append('file', this.selectedFile);
        formData.append('format', this.format);
        formData.append('title', titleInput.value.trim());
        formData.append('csrf_token', window.csrfToken || '');

        try {
            const response = await fetch(this.uploadEndpoint, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                statusSpan.className = 'text-xs text-emerald-700 font-semibold';
                statusSpan.innerHTML = `${data.msg} <a href="/text.php?nodeid=${data.text_neoid}" class="underline ml-2" target="_blank">View text</a>`;
                uploadBtn.textContent = 'Imported';
            } else {
                statusSpan.className = 'text-xs text-rose-600';
                statusSpan.textContent = data.msg || data.error || 'Failed to import pre-annotated text.';
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Retry import';
            }
        } catch (err) {
            console.error('Import error:', err);
            statusSpan.className = 'text-xs text-rose-600';
            statusSpan.textContent = 'Network or server error during import.';
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Retry import';
        }
    }
}
