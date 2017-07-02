<?php
//add engines parts
require_once('constants.php');
require_once('settings.php');
//add necessary modules
require_once(G_ROOT_PATH . '/modules/filemanager/FileManager.php');
require_once(G_ROOT_PATH . '/modules/drawer/Drawer.php');
require_once(G_ROOT_PATH . '/modules/transformer/Transformer.php');
require_once(G_ROOT_PATH . '/modules/dbmanager/DBManager.php');

/**
 * That class contains main hign-level/business logic (model) of solution
 * version 1.0
 * changelog:
 *  - derichev 2017-07-02: initial version
 */
class Engine
{
    /**
     * @var null
     */
    protected  $DBExporter = null;

    /**
     * Engine constructor
     */
    public function __construct() {  }

    /**
     * Engine destructor
     */
    public function __destruct()
    {
        unset($this->DBExporter);
    }

    /**
     * Main method to run the Engine
     */
    public function Run()
    {
        if ($this->IsLoadFileFromWebUI() ) //author note: I don't collapse if code inside intend, just to show the working flow
        {
            //load data from file, which was sent by WebUI
            $file = $this->LoadDataFromSentFileByWebUI($_FILES["filetoupload"]);

            //parse/trasnform data to standard format
            $formattedData = $this->TransformFileData($file["data"]);

            //export transformed data to DB
            $this->ExportFormattedDataToDB($formattedData);

            //change status of random record
            $changedRecord = $this->ChangeStatusOfRandomRecord($formattedData);

            //Show changed record in DB table
            $this->ShowChangedRecord($changedRecord,$file["path"]);
        } elseif ($this->IsLoadFileFromServerFolder() ){
            //load data from the first file, which is located in server folder (path to server folder: see const G_INCOMING_SERVER_FOLDER_PATH_WITH_DATA_FILES)
            $file = $this->LoadDataFromFileInServerFolder();

            //parse/trasnform data to standard format
            $formattedData = $this->TransformFileData($file["data"]);

            //export transformed data to DB
            $this->ExportFormattedDataToDB($formattedData);

            //change status of random record
            $changedRecord = $this->ChangeStatusOfRandomRecord($formattedData);

            //Show changed record in DB table
            $this->ShowChangedRecord($changedRecord,$file["path"]);
        } else {
            // show UI to load file if User has not seleted the file or send request to processing from server data files folder
            $this->ShowUIToLoadFile();
        }
    }

    /**
     * Defines is need to load file from Web UI
     * @return bool
     */
    public function IsLoadFileFromWebUI():bool
    {
        if (isset($_GET['load'])
            and strtoupper($_GET['load']) == 'FROMWEBUI'
            and isset($_FILES["filetoupload"]) ) return true;
        else return false;
    }

    /**
     * Defines is need to load file from Sever Folder
     * @return bool
     */
    public function IsLoadFileFromServerFolder():bool
    {
        if (isset($_GET['load'])
            and strtoupper($_GET['load']) == 'FROMSERVERFOLDER') return true;
        else return false;
    }

    /**
     * Showing the UI to load file (initial page)
     */
    public function ShowUIToLoadFile()
    {
        Drawer::DrawMainPage();
    }

    /**
     * Showing the changed record as result
     * @param iFormattedData $changedRecordInfo
     * @param string $processedFilePath
     */
    public function ShowChangedRecord(iFormattedData $changedRecordInfo, string $processedFilePath)
    {
        //make request to DB to get changed record from there
        //it is important, because another process can change other values of this record
        $indexColumns = $this->GetIndexColumns();
        $foundRecordInDBFormat = $this->DBExporter->GetRecordFromDBByFormattedRecord($changedRecordInfo,G_DB_WAREHOUSE_TABLE_NAME,$indexColumns);
        //Transform found record
        $transformedData = Transformer::ParseData($foundRecordInDBFormat,Transformer::DATA_FORMATS["DBFORMAT"]);
        //set columns types manually
        Transformer::SetColumnTypeInFormattedData($transformedData,'Фамилия Имя',Transformer::DATA_TYPES["STRING"]);
        Transformer::SetColumnTypeInFormattedData($transformedData,'E-mail',Transformer::DATA_TYPES["STRING"]);
        Transformer::SetColumnTypeInFormattedData($transformedData,'Дата рождения',Transformer::DATA_TYPES["DATE"]);
        Transformer::SetColumnTypeInFormattedData($transformedData,'Зарегистрирован',Transformer::DATA_TYPES["INT"]);
        Transformer::SetColumnTypeInFormattedData($transformedData,'Статус',Transformer::DATA_TYPES["STRING"]);
        //transform type to another (as request in design)
        Transformer::ChangeDateFormatInFormattedData($transformedData,'Зарегистрирован','d.m.Y H:i');
        Transformer::SetColumnTypeInFormattedData($transformedData,'Зарегистрирован',Transformer::DATA_TYPES["DATETIME"]);
        //change date format to back for showing
        Transformer::ChangeDateFormatInFormattedData($transformedData,'Дата рождения','d.m.Y');

        //draw result
        Drawer::DrawResult($transformedData);

        //move file to completed folder
        FileManager::MoveToServerDataFilesFolder($processedFilePath , FileManager::MOVE_MODES["PROCESSING_TO_COMPLETED"] );
    }

    /**
     * Method to load data file, which was sent by Web UI
     * @param array $fileInfo
     * @return array, where key "path" defines path to loaded file in server side, key "data" defines data of loaded file
     */
    public function LoadDataFromSentFileByWebUI(array $fileInfo): array
    {
        try {
            $loadedFileTmpPath = $fileInfo["tmp_name"];
            $sizeOfFile = $fileInfo["size"];
            FileManager::MoveToServerDataFilesFolder($loadedFileTmpPath , FileManager::MOVE_MODES["UPLOADED_TO_INCOMING"],$sizeOfFile );
        } catch (Error $er) {
            Drawer::DrawError($er);
            exit();
        }
        $file = $this->LoadDataFromFileInServerFolder();

        return $file;
    }

    /**
     * Method to load data file, which was located in Server Folder
     * @return array, where key "path" defines path to loaded file in server side, key "data" defines data of loaded file
     */
    public function LoadDataFromFileInServerFolder(): array
    {
        $file = array();
        try {
            $firstFilePath = FileManager::GetFirstFilePathInFolder(G_INCOMING_SERVER_FOLDER_PATH_WITH_DATA_FILES);
            $filePathToProcess = FileManager::MoveToServerDataFilesFolder($firstFilePath , FileManager::MOVE_MODES["INCOMING_TO_PROCESSING"] );
            $fileData = FileManager::GetFileContent($filePathToProcess);
        } catch (Error $er)
        {
            if ($er->getCode() == 1001) {
                // directory is empty, nothing to load
                Drawer::DrawWarning($er);
                exit();
            } else {
                Drawer::DrawError($er);
                exit();
            }
        }
        $file["path"]=$filePathToProcess;
        $file["data"]=$fileData;
        return $file;
    }

    /**
     * Transform file data to standard formatted Data
     * @param string $fileData
     * @return iFormattedData
     */
    public function TransformFileData(string $fileData):iFormattedData
    {
        $transformedData = null;
        try {
            //transform file data to standard format
            $transformedData = Transformer::ParseData($fileData,Transformer::DATA_FORMATS["CSV"]);
            //set columns types manually
            Transformer::SetColumnTypeInFormattedData($transformedData,'Фамилия Имя',Transformer::DATA_TYPES["STRING"]);
            Transformer::SetColumnTypeInFormattedData($transformedData,'E-mail',Transformer::DATA_TYPES["STRING"]);
            Transformer::SetColumnTypeInFormattedData($transformedData,'Дата рождения',Transformer::DATA_TYPES["DATE"]);
            Transformer::SetColumnTypeInFormattedData($transformedData,'Зарегистрирован',Transformer::DATA_TYPES["DATETIME"]);
            Transformer::SetColumnTypeInFormattedData($transformedData,'Статус',Transformer::DATA_TYPES["STRING"]);
            //transform type to another (as request in design)
            Transformer::ConvertColumnTypeInFormattedData($transformedData,'Зарегистрирован',Transformer::DATA_TYPES["TIMESTAMP"]);
            //change date format to correct store in DB
            Transformer::ChangeDateFormatInFormattedData($transformedData,'Дата рождения','Y-m-d');
        } catch (Error $er) {
            Drawer::DrawError($er);
            exit();
        }
        return $transformedData;
    }

    /**
     * Export standard formatted data to DB
     * @param iFormattedData $formattedData
     */
    public function ExportFormattedDataToDB(iFormattedData $formattedData)
    {
        $this->DBExporter = new DBManager();
        try {
            //export formatted CSV data to DB in defined table (see settings)
            $indexColumns = $this->GetIndexColumns();
            $this->DBExporter->ExportFormattedDataToDB($formattedData, G_DB_WAREHOUSE_TABLE_NAME,G_DB_WAREHOUSE_TABLE_PRMARY_KEY_COLUMN,$indexColumns);
        } catch (Error $er) {
            Drawer::DrawError($er);
            exit();
        }
    }

    /**
     * Return array of index columns of warehouse table, into which formatted data is exported
     * @return array
     */
    protected function GetIndexColumns():array
    {
        $indexColumns = array();
        for ($i = 0; $i < count(G_DB_WAREHOUSE_TABLE_INDEX_COLUMNS); $i++)
        {
            array_push($indexColumns,G_DB_WAREHOUSE_TABLE_INDEX_COLUMNS[$i]);
        }
        return $indexColumns;
    }

    /**
     * Change status of random record.
     * Return Record which was changed
     * @param iFormattedData $formattedData
     * @return iFormattedData
     */
    public function ChangeStatusOfRandomRecord(iFormattedData $formattedData):iFormattedData
    {
        $formattedSingleRecord = null;
        try {
            //get random record number
            $totalCount = $formattedData->GetDataRowsCount();
            $numRecordToChange = random_int(0,($totalCount-1));

            //change value
            $columnNum = $formattedData->GetColumnNumberByName('Статус');
            $oldValue = $formattedData->GetCellValue($numRecordToChange,$columnNum);
            $newValue = "On";
            if (strtoupper($oldValue) == "ON")
            {
                $newValue = "Off";
            }
            $formattedData->SetCellValue($numRecordToChange,$columnNum,$newValue);

            //update only single changed record in DB table
            $formattedSingleRecord = $formattedData->GetFormattedRecord($numRecordToChange);
            $indexColumns = $this->GetIndexColumns();
            $this->DBExporter->ExportFormattedDataToDB($formattedSingleRecord, G_DB_WAREHOUSE_TABLE_NAME,G_DB_WAREHOUSE_TABLE_PRMARY_KEY_COLUMN,$indexColumns);
        } catch (Error $er) {
            Drawer::DrawError($er);
            exit();
        }

        return $formattedSingleRecord ;
    }
}