unit Kernel;

interface
uses
  Windows;

  procedure SetKeys;

  function WinExecInjectAndWait32(const ExeFileName, CommandLine, DllFileName: string;
                                  Visibility: Integer): Integer;
  procedure SaveCmdLineToFile;

implementation

uses
  CutSysUtils, Inject, GlobalVar, WrapFunc;

var
  CmdFile: Text;
  

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function WinExecInjectAndWait32(const ExeFileName, CommandLine, DllFileName: string;
                                Visibility: Integer): Integer;
var
  StartupInfo: TStartupInfo;
  ProcessInfo: TProcessInformation;
  Res: UINT;
  FullCommandLine: string;
begin
  Result := -1;
  if not FileExists(ExeFileName) or not FileExists(DllFileName) then
     Exit;

  FillChar(StartupInfo, Sizeof(StartupInfo), #0);
  StartupInfo.cb := Sizeof(StartupInfo);
  StartupInfo.dwFlags := STARTF_USESHOWWINDOW;
  StartupInfo.wShowWindow := Visibility;

  FullCommandLine := QuotedString(ExeFileName, '"') + ' ' + CommandLine;

  if not (CreateProcess(PChar(ExeFileName),
    PChar(FullCommandLine),{ pointer to command line string }
    nil,                   { pointer to process security attributes}
    nil,                   { pointer to thread security attributes }
    false,                 { handle inheritance flag }
    CREATE_SUSPENDED,      { creation flags }
    nil,                   { pointer to new environment block }
    nil,                   { pointer to current directory name }
    StartupInfo,           { pointer to STARTUPINFO }
    ProcessInfo)) then     { pointer to PROCESS_INF }
    Result := -1
  else
  begin
    { Инжектим dll и резюмируем процесс }
     if InjectLib(DllFileName, ProcessInfo.dwProcessId) then
       begin
         _ResumeThread(ProcessInfo.hThread);

        { Ждём завершения праздгника }
         WaitforSingleObject(ProcessInfo.hProcess, INFINITE);
         GetExitCodeProcess(ProcessInfo.hProcess, Res);
       end
     else
        TerminateProcess(ProcessInfo.hProcess, 1);

     CloseHandle(ProcessInfo.hProcess);
     CloseHandle(ProcessInfo.hThread);
     Result := Res;
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure SetKeys;
begin
  gK1 := 4517;
  gK2 := 625439;
  gK3 := 947451;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure SaveCmdLineToFile;
var
  cmdFilePath, sCmdLine: string;
  Idx: Integer;
begin
  // Удаляем старый файл с командной строкой
  cmdFilePath := ExtractFilePath(ParamStr(0)) + DecStr(str_cmdtmp);
  if FileExists(cmdFilePath) then
     Exit;

  sCmdLine := GetCommandLine;
  idx := Pos(' ', sCmdLine);
  sCmdLine := Copy(sCmdLine, Idx + 1, Length(sCmdLine) - Idx);
  sCmdLine := QuotedString(ParamStr(0), '"') + sCmdLine;

//  sCmdLine := QuotedString(ParamStr(0), '"');
//  for i := 1 to ParamCount do
//    sCmdLine := sCmdLine + ' ' + ParamStr(i);

  AssignFile(CmdFile, cmdFilePath);
{$I-}
  Rewrite(CmdFile);
  Append(CmdFile);
  WriteLn(CmdFile, sCmdLine);
  CloseFile(CmdFile);
{$I+}
end;

end.
