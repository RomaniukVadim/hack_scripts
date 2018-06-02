 unit Hooked;

interface

uses
  CodeBreak, Windows;

var
  CreateProcessWNext: TCreateProcessWProc;
  LoadLibraryWNext:  TLoadLibraryWProc;
  F5Next: TF5Proc;
  ShellExecuteWNext: TShellExecuteWProc;
  ShellExecuteExWNext: TShellExecuteExWProc;
  MessageBoxWNext: TMessageBoxWProc;
  CoCreateInstanceNext: TCoCreateInstanceProc;

  function CreateProcessWCallBack(lpApplicationName: PWideChar; lpCommandLine: PWideChar;
              lpProcessAttributes, lpThreadAttributes: PSecurityAttributes;
              bInheritHandles: BOOL; dwCreationFlags: DWORD; lpEnvironment: Pointer;
              lpCurrentDirectory: PWideChar; const lpStartupInfo: TStartupInfoW;
              var lpProcessInformation: TProcessInformation): BOOL; stdcall;

  function LoadLibraryWCallBack(lpLibFileName: PWideChar): HMODULE; stdcall;

  function F5CallBack(lpszUsername: LPCWSTR; lpszPassword: LPCWSTR): Integer; stdcall;

  function ShellExecuteWCallBack(hWnd: HWND; Operation, FileName, Parameters,
                                 Directory: PWideChar; ShowCmd: Integer): HINST; stdcall;

  function ShellExecuteExWCallBack(lpExecInfo: PShellExecuteInfoW):BOOL; stdcall;

  function MessageBoxWCallBack(hWnd: HWND; lpText, lpCaption: PWideChar;
                               uType: UINT): Integer; stdcall;

  function CoCreateInstanceCallBack(const clsid: TCLSID; unkOuter: IUnknown; dwClsContext: Longint;
                                    const iid: TIID; var pv): HResult; stdcall;



implementation

uses
  Kernel, CutSysUtils, WrapFunc, GlobalVar;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function GetPathFromCommanLine(const ACommandLine: string): string;
var
  Idx: Integer;
begin
  Result := ACommandLine;

  Idx := Pos(' ', ACommandLine);
  if Idx > 0 then
    begin
      Result := Copy(ACommandLine, 1, Idx - 1);
      Result := ExtractQuotedString(Result, '"');
      Result := ExtractFilePath(Result);
    end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function CreateProcessWCallBack(lpApplicationName: PWideChar; lpCommandLine: PWideChar;
              lpProcessAttributes, lpThreadAttributes: PSecurityAttributes;
              bInheritHandles: BOOL; dwCreationFlags: DWORD; lpEnvironment: Pointer;
              lpCurrentDirectory: PWideChar; const lpStartupInfo: TStartupInfoW;
              var lpProcessInformation: TProcessInformation): BOOL; stdcall;
var
  wS: WideString;
  sCommandLine,
  ExeStartPath: string;
//  StarterPath, StarterParams: string;
begin
  //DbgStr('CreateProcessW');

//  if lpApplicationName = nil then
//      DbgStr('nil app')
//  else
//    begin
//      WideCharToStrVar(lpApplicationName, S);
//      DbgStr(S);
//    end;

  WideCharToStrVar(lpCommandLine, sCommandLine);
  sCommandLine := LowerCase(sCommandLine);
  //DbgStr(sCommandLine);

 { Получаем нормальный путь к запускаемому процессу, без параметров }
  if Pos(DecStr(str_dottmp), sCommandLine) > 0 then
    begin
      ExeStartPath := GetPathFromCommanLine(sCommandLine);
      //DbgStr('ExeStartPath: ' + ExeStartPath);

     { Копируем себя в директорию c personal-monitor.tmp под именем msimg32.dll для автоинжекта }
      AutoInjDllPath := ExeStartPath + DecStr(dll_msimg32);
      CopyFile(PChar(GetDllFilePath),
               PChar(AutoInjDllPath), False);
     { Копируем файл с командной строкой стартера }          
      CopyFile(PChar(ExtractFilePath(GetDllFilePath) + DecStr(str_cmdtmp)),
               PChar(ExeStartPath + DecStr(str_cmdtmp)), False);
    end else
  if (Pos(DecStr(str_mpkexe), sCommandLine) > 0) and
     (Pos(DecStr(str_cmdexe), sCommandLine) = 0) then
    begin
      wS := Copy(sCommandLine, 1, Pos(' ', sCommandLine) - 1);
      lpCommandLine := PWideChar(wS);
      //DbgStr('NewComLine');
      //OutputDebugStringW(lpCommandLine);

      // Копируем ключевой файл
//      if SplitStarterPath(GetExeFilePath + DecStr(str_cmdtmp),
//                          StarterPath, StarterParams) then
//        begin
//          Windows.CopyFile(PChar(ExtractFilePath(StarterPath) + DecStr(str_keybin)),
//                           PChar(ExtractFilePath(GetPathFromCommanLine(sCommandLine)) + DecStr(str_keybin)),
//                           False);
//        end;
    end;

  Result := CreateProcessWNext(lpApplicationName, lpCommandLine, lpProcessAttributes,
                               lpThreadAttributes,
                               bInheritHandles, dwCreationFlags, lpEnvironment,
                               lpCurrentDirectory, lpStartupInfo,
                               lpProcessInformation);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function F5CallBack(lpszUsername: LPCWSTR; lpszPassword: LPCWSTR): Integer; stdcall;
begin
  Result := ERROR_SUCCESS;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function LoadLibraryWCallBack(lpLibFileName: PWideChar): HMODULE; stdcall;
var
  S: string;
begin
 { Конвертируем имя в string и проверям на библиотеку защиты }
  WideCharToStrVar(lpLibFileName, S);
  S := LowerCase(ExtractFileName(S));
  if S = DecStr(dll_mpkf) then
    begin
      Result := LoadLibraryWNext(lpLibFileName);

      if not UniHook(GetFuncAddr(DecStr(fnc_F5), DecStr(dll_mpkf)), F5CallBack, F5Next) then
        begin
          //DbgStr('F5' + ' hook failed.');
        end;
    end
  else
      Result := LoadLibraryWNext(lpLibFileName);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function ShellExecuteWCallBack(hWnd: HWND; Operation, FileName, Parameters,
                               Directory: PWideChar; ShowCmd: Integer): HINST; stdcall;
var
  S: string;
begin
 { Конвертируем имя в string и проверям на библиотеку защиты }
  WideCharToStrVar(FileName, S);
  //DbgStr('Shell :' + S);

  Result := ShellExecuteWNext(hWnd, Operation, FileName, Parameters,
                              Directory, ShowCmd);
end;

function GetProcessId(Process: THandle): DWORD; stdcall; external kernel32 name 'GetProcessId';

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function MessageBoxWCallBack(hWnd: HWND; lpText, lpCaption: PWideChar;
                             uType: UINT): Integer; stdcall;
begin
  if Pos(DecStr(str_ctrlaltshift), lpText) > 0 then
     Result := ID_OK
  else
     Result := MessageBoxWNext(hWnd, lpText, lpCaption, uType);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function CoCreateInstanceCallBack(const clsid: TCLSID; unkOuter: IUnknown; dwClsContext: Longint;
                                  const iid: TIID; var pv): HResult; stdcall;
const
  CLSID_ShellLink: TGUID = (
    D1:$00021401; D2:$0000; D3:$0000; D4:($C0,$00,$00,$00,$00,$00,$00,$46));
begin
  if IsEqualGUID(clsid, CLSID_ShellLink) then
    begin
      //DbgStr('ShGUID');
      Result := E_NOINTERFACE;
    end
  else
      Result := CoCreateInstanceNext(clsid, unkOuter, dwClsContext, iid, pv);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function ShellExecuteExWCallBack(lpExecInfo: PShellExecuteInfoW):BOOL; stdcall;
var
  S, StarterPath, StarterParams: string;
  wS: WideString;
begin
 { Проверка на запуск эскалации привелегий через runas }
  WideCharToStrVar(lpExecInfo^.lpVerb, S);
  if LowerCase(S) = DecStr(str_runas) then
    begin
      //DbgStr('runas escalation');

      if SplitStarterPath(GetExeFilePath + DecStr(str_cmdtmp),
                          StarterPath, StarterParams) then
        begin
          // Имя запускаемого файла
          wS := StarterPath;
          lpExecInfo^.lpFile := PWideChar(wS);

          // Складываем параметры стартера с переданными
          WideCharToStrVar(lpExecInfo^.lpParameters , S);
          //DbgStr('params: ' + S);
          wS := StarterParams + ' ' + QuotedString(S, '"');
          lpExecInfo^.lpParameters := PWideChar(wS);

          // Прячем стартер
          lpExecInfo^.nShow := SW_HIDE;
        end;
    end;

 { Не даём открывать ссылки браузеру }   
  WideCharToStrVar(lpExecInfo^.lpFile , S);
  if Pos(DecStr(str_http), S) > 0 then
    begin
      //DbgStr('Locking browser');
      Result := False;
      Exit;
    end;

//  DbgStr('lpFile: ' + S);
//  wS := ExtractFilePath(S) + 'MpLoader.exe';
//  lpExecInfo^.lpFile := PWideChar(wS);
//
//  WideCharToStrVar(lpExecInfo^.lpParameters , S);
//  DbgStr('params: ' + S);
//
//  wS := '-e personal-monitor.exe -l 1.dll -c ' + QuotedString(S, '"');
//  lpExecInfo^.lpParameters := PWideChar(wS);
//
//
//  WideCharToStrVar(lpExecInfo^.lpDirectory, S);
//  DbgStr('dir: ' + S);
//
//  S := IntToStr(lpExecInfo^.nShow);
//  DbgStr('nShow: ' + S);
//  lpExecInfo^.nShow := SW_HIDE;


  Result := ShellExecuteExWNext(lpExecInfo);
  //DbgStr('hProcess: ' + IntToStr(GetProcessId(lpExecInfo.hProcess)));
end;


end.
