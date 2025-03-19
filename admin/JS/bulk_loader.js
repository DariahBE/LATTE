class CSVHandler {
    constructor(containerId, uploadEndpoint = '') {
        this.container = document.getElementById(containerId);
        this.uploadEndpoint = uploadEndpoint;
        this.csvData = [];
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
                <div class="flex items-stretch ">
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
                <div id="mapping" class="mt-4"></div>
                <div id="progressContainer" class="mt-4 hidden"></div>
            </div>
            <div>
                <button id="mapTrigger" class="bg-green-400">Make mapping</button>
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
    }

    updateSettings() {
        this.delimiter = document.getElementById('delimiter').value;
        this.enclosure = document.getElementById('enclosure').value;
        this.skipHeader = document.getElementById('hasheader').checked; 
        console.log(this.skipHeader); 
        this.previewRows = parseInt(document.getElementById('previewRows').value);
        if (this.csvData.length) {
            this.csvData = this.parseCSV(this.file_content); 
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
            this.csvData = this.parseCSV(content);
            this.renderPreview();
        };
        reader.readAsText(file);
    }

    parseCSV(content) {
        const rows = content.split(this.lineEnding);
        const parsedRows = rows.map(row => this.parseRow(row));
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
        this.headerNames.forEach((header) => { 
            const th = document.createElement('th'); 
            th.className = "border p-2 truncate max-w-xs overflow-hidden"; 
            th.textContent = header; 
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
        // if (this.csvData.length) {
        //     this.renderMappingUI();
        // }
    }

    renderMappingUI() {
        const mappingDiv = document.getElementById('mapping');
        mappingDiv.innerHTML = '<h3 class="font-bold mb-2">Map CSV Columns</h3>';
        if (!this.csvData.length) return;

        const csvHeaders = this.headerNames;

        Object.entries(this.datamodel).forEach(([key, value]) => {
            const div = document.createElement('div');
            div.className = "mb-2";
            // Create a label using the data model key
            const label = document.createElement('label');
            label.textContent = `Column: ${value[0]}`; // Use the data model key as the label
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
            csvHeaders.forEach((header) => {
                const option = document.createElement('option');
                option.value = header; // Set the CSV header as the value
                option.textContent = header; // Set the CSV header as the display text
                select.appendChild(option);
            });
            div.appendChild(select);
            mappingDiv.appendChild(div);
        });

    }

    uploadData() {
        if (!this.uploadEndpoint) return;
        
        document.getElementById('progressContainer').classList.remove('hidden');
        let progress = 0;

        const uploadRow = (index) => {
            if (index >= this.csvData.length) {
                document.getElementById('progressContainer').innerHTML = '<p class="text-green-600">Upload completed</p>';
                return;
            }
            fetch(this.uploadEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.csvData[index])
            }).then(() => {
                progress++;
                uploadRow(progress);
            });
        };
        uploadRow(0);
    }
}
