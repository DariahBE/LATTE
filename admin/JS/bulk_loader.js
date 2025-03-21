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
            <div class="p-4 border rounded-lg shadow-md">
                <div id="csv_settings" class="flex items-stretch ">
                    <div class="mb-4  sm:w-1/2 md:w-1/4  sm:m-1 md:m-2 sm:p-1 md:p-2">
                        <label class="block mb-2">Delimiter:</label>
                        <input id="delimiter" type="text" value="," class="border p-2 rounded w-full">
                    </div>
                    <div class="mb-4  sm:w-1/2 md:w-1/4  sm:m-1 md:m-2 sm:p-1 md:p-2">
                        <label class="block mb-2">Enclosed By:</label>
                        <input id="enclosure" type="text" value='"' class="border p-2 rounded w-full">
                    </div>
                    <div class="mb-4 sm:w-1/2 md:w-1/4  sm:m-1 md:m-2 sm:p-1 md:p-2">
                        <label class="block mb-2">Rows to Preview:</label>
                        <input id="previewRows" type="number" value="5" class="border p-2 rounded w-full">
                    </div>
                    <div class="mb-4  sm:w-1/2 md:w-1/4  sm:m-1 md:m-2 sm:p-1 md:p-2">
                        <label class="block mb-2">File has header:</label>
                        <input id="hasheader" type="checkbox">
                    </div>
                </div>
                <div class="border-dashed border-2 border-gray-300 p-6 text-center cursor-pointer bg-gray-100 hover:bg-gray-200" id="dropzone">
                    Drop CSV file here
                </div>
                <div id="preview" class="mt-4 overflow-auto max-h-64"></div>
                <div>
                    <button id="mapTrigger" class="bg-green-400 disabled:green-200 disabled:cursor-not-allowed">Make mapping</button>
                </div>
                <div id="mapping" class="mt-4"></div>
                <div id="do_upload">
                    <button id="upload" class="bg-green-400 disabled:green-200 disabled:cursor-not-allowed" disabled>Upload</button>
                </div>
                <div id="progressContainer" class="mt-4 hidden">
                    <div id="message_upload_progress"><p><span id="messagecontent_progress">Upload in progress</span><span id="floatcontent_progress"></span</p></div>
                    <div class='w-full  bg-gray-100 rounded-3xl h-2.5 '>
                    <div id="progressBarAnimation" class='bg-indigo-600 h-2.5 rounded-3xl' style='width: 0%'></div>
                </div>
            </div>

        `;

        this.addEventListeners();
    }

    addEventListeners() {
        document.getElementById('dropzone').addEventListener('drop', (e) => this.handleDrop(e));
        document.getElementById('dropzone').addEventListener('dragover', (e) => this.handleDrag(e));
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
        // console.log(this.skipHeader); 
        this.previewRows = parseInt(document.getElementById('previewRows').value);
        if (this.csvData.length) {
            this.csvData = this.parseCSV(this.file_content, false); //small slice = faster preview
            this.renderPreview();
        }
    }

    handleDrag(event){
        event.preventDefault();
        event.stopPropagation(); 
    } 

    handleDrop(event) {
        event.preventDefault();
        const file = event.dataTransfer.files[0];
        const validMimeTypes = ['text/csv', 'application/csv', 'application/vnd.ms-excel'];
        if (file && validMimeTypes.includes(file.type)) {
            this.readCSV(file);
        }
    }

    readCSV(file) {
        //
        const reader = new FileReader();
        reader.onload = (e) => {
            const content = e.target.result;
            this.file_content = content; 
            this.csvData = this.parseCSV(content, false); //small slice = faster preview
            this.renderPreview();
        };
        reader.readAsText(file);
    }

    parseCSV(content, full = false) {
        const rows = content.split(this.lineEnding);
        const parsedRows = rows.map(row => this.parseRow(row));
        if(full){return parsedRows;}
        return parsedRows.slice(0, this.previewRows);
    }
    parseRow(row) {
        const regex = new RegExp(
            `${this.enclosure}([^${this.enclosure}]+|${this.enclosure}${this.enclosure})*${this.enclosure}|[^${this.delimiter}]+`,
            'g'
        );
        const matches = row.match(regex);
        
        // If matches are found, return them split by the delimiter
        if (matches) {
            return matches.map(match => {
                // Remove enclosing characters and replace double enclosures
                return match.replace(new RegExp(`^${this.enclosure}|${this.enclosure}$`, 'g'), '')
                            .replace(new RegExp(`${this.enclosure}${this.enclosure}`, 'g'), this.enclosure);
            });
        }
        return []; // Return an empty array if no matches found
    }

    renderPreview() {
        const previewDiv = document.getElementById('preview');
        previewDiv.innerHTML = '';
        const table = document.createElement('table');
        table.className = "w-full border-collapse border border-gray-300 text-sm";
        this.headerNames = [];
        if(this.skipHeader){
            this.csvData[0].forEach((cell) => {
                this.headerNames.push(cell);
            })
        }else{
            let i = 0; 
            while(i <= (this.csvData[0].length - 1) ){
                this.headerNames.push(`Column_${i}`);
                i++;
            }
        }
        let tr = document.createElement('tr'); 
        let j = 0; 
        this.headerNames.forEach((header) => { 
            const th = document.createElement('th'); 
            th.className = "border p-2 truncate max-w-xs overflow-hidden"; 
            th.textContent = header; 
            th.setAttribute('data-col', j);
            j++;
            tr.appendChild(th); 
        }); 
        table.appendChild(tr);
        this.csvData.forEach((row, rowIndex) => {
            // Skip the first row if this.skipHeader is true
            if (this.skipHeader && rowIndex === 0) {
                return; // Skip this iteration
            }
            const tr = document.createElement('tr');
            row.forEach((cell) => {
                const td = document.createElement('td');
                td.className = "border p-2 truncate max-w-xs overflow-hidden";
                td.textContent = cell;
                tr.appendChild(td);
            });
            table.appendChild(tr);
        });
        previewDiv.appendChild(table);
    }

    setDatamodel(datamodelObject) {
        this.datamodel = datamodelObject;
    }

    renderMappingUI() {
        //enable the upload button: 
        document.getElementById('upload').removeAttribute('disabled'); 
        //disable CSV dropzone and change elements: 
        document.getElementById('dropzone').classList.add('hidden');
        document.getElementById('csv_settings').classList.add('hidden');
        const mappingDiv = document.getElementById('mapping');
        mappingDiv.innerHTML = '<h3 class="font-bold mb-2">Map CSV Columns</h3>';
        if (!this.csvData.length) return;

        const csvHeaders = this.headerNames;

        Object.entries(this.datamodel).forEach(([key, value]) => {
            const div = document.createElement('div');
            div.className = "mb-2 row grid";
            // Create a label using the data model key
            const label = document.createElement('label');
            label.innerHTML = `<span class="font-bold" >Column</span>: <span class='font-semibold'>${value[0]}</span> (${value[1]})`; // Use the data model key as the label
            div.appendChild(label);
            const select = document.createElement('select');
            select.setAttribute('data-for-neocol', key); 
            select.className = "border p-2 rounded w-full";
            //have a prompt in select: 
            const defaultOption = document.createElement('option');
            defaultOption.value = ""; // Set value to an empty string
            defaultOption.textContent = "Choose a column of your CSV file"; // Default text
            defaultOption.disabled = true; // Disable the default option
            defaultOption.selected = true; // Make it the selected option
            select.appendChild(defaultOption);
            // Use the CSV headers as options in the dropdown
            let j = 0; 
            csvHeaders.forEach((header) => {
                const option = document.createElement('option');
                option.value = header; // Set the CSV header as the value
                option.setAttribute('data-col', j);
                j++;
                option.textContent = header; // Set the CSV header as the display text
                select.appendChild(option);
                // console.log(option); 
            });
            div.appendChild(select);
            mappingDiv.appendChild(div);
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
            let selectedIdx = selectedOption.getAttribute('data-col');
            if (selectedIdx !== null) {
                final_mapping[neocol] = [selectedColumn, parseInt(selectedIdx)];
            }
        }); 
        
        const csv_cols = Object.values(final_mapping).map(mapping => mapping[1]); 
        const keys = Object.keys(final_mapping);
        
        document.getElementById('progressContainer').classList.remove('hidden');
        document.getElementById('mapping').classList.add('hidden');
        document.getElementById('preview').classList.add('hidden');
        document.getElementById('mapTrigger').classList.add('hidden');
        document.getElementById('upload').classList.add('hidden');
        
        let progress = 0;
        const totalRows = this.csvData.length;
                
        const uploadBatch = async (startIndex) => {
            if (startIndex >= totalRows) {
                // set container completed: 
                document.getElementById('progressBarAnimation').style.width = '100%';
                document.getElementById("progressBarAnimation").classList.add("bg-green-500", "h-6");
                document.getElementById('messagecontent_progress').textContent = 'Upload completed';
                document.getElementById('floatcontent_progress').classList.add('hidden');
                document.getElementById("progressBarAnimation").classList.remove("bg-indigo-600", "h-2.5");
                return;
            }
            
            const endIndex = Math.min(startIndex + this.batchSize, totalRows);
            const batch = this.csvData.slice(startIndex, endIndex);
            
            let uploadDataBatch = batch.map(row => 
                csv_cols.filter(index => !isNaN(index) && index >= 0 && index < row.length)
                        .map(index => row[index])
            );
            
            const formData = new FormData();
            formData.append('keys', JSON.stringify(keys));  // Convert array to JSON string
            formData.append('data', JSON.stringify(uploadDataBatch));
    
            try {
                const response = await fetch(this.uploadEndpoint, {
                    method: 'POST',
                    body: formData // No need for Content-Type header; browser sets it automatically
                });
                const jsonResponse = await response.json();
                console.log(jsonResponse);
                if ('error' in jsonResponse) {
                    progress = totalRows;  //skip all rows. no point in continuing.
                }
                
                progress += batch.length;
                uploadBatch(endIndex);
                this.update_progress_bar(progress, totalRows);
            } catch (error) {
                console.error('Error uploading batch:', error);
                document.getElementById('progressContainer').innerHTML = '<p class="text-red-600">An error occurred during upload.</p>';
            }
        };
        
        let rowstart = this.skipHeader ? 1 : 0;
        uploadBatch(rowstart);
    }

    update_progress_bar(progress, total){
        let completionrate = progress/total; 
        let progressPercent = document.getElementById('floatcontent_progress');
        progressPercent.textContent = `(${Math.floor(completionrate*100)}%)`;
        let progressBar = document.getElementById('progressBarAnimation');
        progressBar.style.width = `${completionrate*100}%`;
    }
    




}
