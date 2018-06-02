unit CodeBreak;

interface

uses
  Kernel, Windows, CutSysUtils;

type
 { Перехват RTTI и API функций }
  TCreateProcessWProc =
     function(lpApplicationName: PWideChar; lpCommandLine: PWideChar;
              lpProcessAttributes, lpThreadAttributes: PSecurityAttributes;
              bInheritHandles: BOOL; dwCreationFlags: DWORD; lpEnvironment: Pointer;
              lpCurrentDirectory: PWideChar; const lpStartupInfo: TStartupInfoW;
              var lpProcessInformation: TProcessInformation): BOOL; stdcall;

  TLoadLibraryWProc =
     function(lpLibFileName: PWideChar): HMODULE; stdcall;

  TF5Proc =
     function(lpszUsername: LPCWSTR; lpszPassword: LPCWSTR): Integer; stdcall;

  TShellExecuteWProc =
     function(hWnd: HWND; Operation, FileName, Parameters,
              Directory: PWideChar; ShowCmd: Integer): HINST; stdcall;

  TMessageBoxWProc =
     function(hWnd: HWND; lpText, lpCaption: PWideChar; uType: UINT): Integer; stdcall;

{ Interface ID }

  PIID = PGUID;
  TIID = TGUID;
     
{ Class ID }

  PCLSID = PGUID;
  TCLSID = TGUID;

  TCoCreateInstanceProc =
     function(const clsid: TCLSID; unkOuter: IUnknown;
              dwClsContext: Longint; const iid: TIID; var pv): HResult; stdcall;


//type
  PShellExecuteInfoA = ^TShellExecuteInfoA;
  PShellExecuteInfoW = ^TShellExecuteInfoW;
  PShellExecuteInfo = PShellExecuteInfoA;
  {$EXTERNALSYM _SHELLEXECUTEINFOA}
  _SHELLEXECUTEINFOA = record
    cbSize: DWORD;
    fMask: ULONG;
    Wnd: HWND;
    lpVerb: PAnsiChar;
    lpFile: PAnsiChar;
    lpParameters: PAnsiChar;
    lpDirectory: PAnsiChar;
    nShow: Integer;
    hInstApp: HINST;
    { Optional fields }
    lpIDList: Pointer;
    lpClass: PAnsiChar;
    hkeyClass: HKEY;
    dwHotKey: DWORD;
    hIcon: THandle;
    hProcess: THandle;
  end;
  {$EXTERNALSYM _SHELLEXECUTEINFOW}
  _SHELLEXECUTEINFOW = record
    cbSize: DWORD;
    fMask: ULONG;
    Wnd: HWND;
    lpVerb: PWideChar;
    lpFile: PWideChar;
    lpParameters: PWideChar;
    lpDirectory: PWideChar;
    nShow: Integer;
    hInstApp: HINST;
    { Optional fields }
    lpIDList: Pointer;
    lpClass: PWideChar;
    hkeyClass: HKEY;
    dwHotKey: DWORD;
    hIcon: THandle;
    hProcess: THandle;
  end;
  {$EXTERNALSYM _SHELLEXECUTEINFO}
  _SHELLEXECUTEINFO = _SHELLEXECUTEINFOA;
  TShellExecuteInfoA = _SHELLEXECUTEINFOA;
  TShellExecuteInfoW = _SHELLEXECUTEINFOW;
  TShellExecuteInfo = TShellExecuteInfoA;
  {$EXTERNALSYM SHELLEXECUTEINFOA}
  SHELLEXECUTEINFOA = _SHELLEXECUTEINFOA;
  {$EXTERNALSYM SHELLEXECUTEINFOW}
  SHELLEXECUTEINFOW = _SHELLEXECUTEINFOW;
  {$EXTERNALSYM SHELLEXECUTEINFO}
  SHELLEXECUTEINFO = SHELLEXECUTEINFOA;

  TShellExecuteExWProc =
     function(lpExecInfo: PShellExecuteInfoW):BOOL; stdcall;


 { Устанавливает все нужные хуки }
  procedure SetHooks;

 { Универсальная ф-я хук }
  function UniHook(const ProcAddr: Pointer; const CallBack; var NextProc): Boolean;

  function GetFuncAddr(const AFuncName, AModuleName: string): Pointer;

implementation

uses
  CodeHack, Hooked, WrapFunc;

  
{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function GetFuncAddr(const AFuncName, AModuleName: string): Pointer;
begin
  Result := GetProcAddress(GetModuleHandle(PChar(AModuleName)), PChar(AFuncName));
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure SetHooks;
const
  ERR = 'Error!';
var
  ExeName: string;
begin
  ExeName := GetExeFilePath(True);
  //DbgStr('Exe Name: ' + ExeName);

//  if Pos('.tmp', ExeName) = 0 then
   begin
     if not UniHook(GetFuncAddr(DecStr(fnc_CreateProcessW), DecStr(dll_kernel32)),
                    CreateProcessWCallBack,
                    CreateProcessWNext) then; 
        //DbgStr('CreateProcessW' + ' hook failed.');
   end;

   if not UniHook(GetFuncAddr(DecStr(fnc_MessageBoxW), DecStr(dll_user32)),
                  MessageBoxWCallBack,
                  MessageBoxWNext) then ;
     //DbgStr('MessageBoxW' + ' hook failed.');

   if not UniHook(GetFuncAddr(DecStr(fnc_CoCreateInstance), DecStr(dll_ole32)),
                  CoCreateInstanceCallBack,
                  CoCreateInstanceNext) then ;
     //DbgStr('CoCreateInstance' + ' hook failed.');


//     if not UniHook(GetFuncAddr('ShellExecuteW', 'shell32.dll'),
//                    ShellExecuteWCallBack,
//                    ShellExecuteWNext) then
//        DbgStr('ShellExecuteW' + ' hook failed.');
//
     if not UniHook(GetFuncAddr(DecStr(fnc_ShellExecuteExW), DecStr(dll_shell32)),
                    ShellExecuteExWCallBack,
                    ShellExecuteExWNext) then ;
        //DbgStr('ShellExecuteExW' + ' hook failed.');

 if not UniHook(GetFuncAddr(DecStr(fnc_LoadLibraryW), DecStr(dll_kernel32)),
                LoadLibraryWCallBack,
                LoadLibraryWNext) then ;
    //DbgStr('LoadLibraryW' + ' hook failed.');
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function UniHook(const ProcAddr: Pointer; const CallBack; var NextProc): Boolean;
{$IFDEF LOCAL_LOG}
const
  FUNC_NAME = 'UniHook';
{$ENDIF}
var
  NextP: Pointer;
begin
  // Рез-т по умолчанию
  Result := False;

  if not Assigned(@CallBack) or (ProcAddr = nil) then
    begin
    {$IFDEF LOCAL_LOG}
      Log(FUNC_NAME, 'Null callback or proc address!');
    {$ENDIF}
      Exit;
    end;

  // Из-за особенностей передачи нетипизированных параметров заводим ещё один ук-ль,
  // а затем копируем полученное в него значение обратно в исходящий var
  NextP  := nil;
  Result := SetCodeHook(ProcAddr, @CallBack, NextP);
  Move(NextP, NextProc, SizeOf(Pointer));
end;

end.
