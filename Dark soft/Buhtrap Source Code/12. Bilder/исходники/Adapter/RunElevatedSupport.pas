unit RunElevatedSupport;

{$WARN SYMBOL_PLATFORM OFF}
{$R+}

interface

uses
  Windows;

type
  TElevatedProc        = function(const AParameters: String): Cardinal;
  TProcessMessagesMeth = procedure of object;

  TTokenInfo = record
    TokenHandle: THANDLE;
    SidToCheck: Pointer;
    IsMember: BOOL;
  end;

var
  // Warning: this function will be executed in external process.
  // Do not use any global variables inside this routine!
  // Use only supplied AParameters.
  OnElevateProc: TElevatedProc;

// Call this routine after you have assigned OnElevateProc
procedure CheckForElevatedTask;

// Runs OnElevateProc under full administrator rights
function RunElevated(const AParameters: String; const AWnd: HWND = 0; const AProcessMessages: TProcessMessagesMeth = nil): Cardinal; overload;

function  IsAdministrator: Boolean;
function  IsAdministratorAccount: Boolean;
function  IsUACEnabled: Boolean;
function  IsElevated: Boolean;
procedure SetButtonElevated(const AButtonHandle: THandle);


function _CheckTokenMembership(TokenHandle: THANDLE; SidToCheck: Pointer;
                               var IsMember: BOOL): BOOL; stdcall;


implementation

uses
  SysUtils, Registry, ShellAPI, ComObj, Crypto, PEB;

const
  RunElevatedTaskSwitch = '0CC5C50CB7D643B68CB900BF000FFFD5'; // some unique value, just a GUID with removed '[', ']', and '-'

var
  gKernelBaseAddress: HMODULE;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function HideLoadLibrary(const FileName: string): HModule;
var
  pLoadLibrary: function (lpLibFileName: PAnsiChar): HMODULE; stdcall;
  dwBaseAddress: DWORD;
begin
  Result := 0;
  pLoadLibrary := nil;
  dwBaseAddress := 0;

  asm
    mov     eax,[fs:$30]  // Peb
    mov     eax,[eax+$C]  // LDR
    mov     eax,[eax+$C]  // InLoadOrderModuleList
    mov     eax,[eax]   // [_LDR_MODULE.InLoadOrderModuleList].Blink kernelbase.dll
    mov     eax,[eax]    //[_LDR_MODULE.InLoadOrderModuleList].Blink kernel32.dll
    mov     eax,[eax+$18] //[_LDR_MODULE.InLoadOrderModuleList]. BaseAddress

    mov dwBaseAddress, eax
  end;

  gKernelBaseAddress := HMODULE(dwBaseAddress);
//  pLoadLibrary := GetProcAddress(dwBaseAddress, 'LoadLibraryA');
  if Assigned(pLoadLibrary) then
     Result := pLoadLibrary(PChar(FileName));
end;

function RunElevated(const AParameters: String; const AWnd: HWND = 0; const AProcessMessages: TProcessMessagesMeth = nil): Cardinal; overload;
var
  SEI: TShellExecuteInfo;
  Host: String;
  Args: String;
begin
  Assert(Assigned(OnElevateProc), 'OnElevateProc must be assigned before calling RunElevated');

  if IsElevated then
  begin
    if Assigned(OnElevateProc) then
      Result := OnElevateProc(AParameters)
    else
      Result := ERROR_PROC_NOT_FOUND;
    Exit;
  end;


  Host := ParamStr(0);
  Args := Format('/%s %s', [RunElevatedTaskSwitch, AParameters]);

  FillChar(SEI, SizeOf(SEI), 0);
  SEI.cbSize := SizeOf(SEI);
  SEI.fMask := SEE_MASK_NOCLOSEPROCESS;
  {$IFDEF UNICODE}
  SEI.fMask := SEI.fMask or SEE_MASK_UNICODE;
  {$ENDIF}
  SEI.Wnd := AWnd;
  SEI.lpVerb := 'runas';
  SEI.lpFile := PChar(Host);
  SEI.lpParameters := PChar(Args);
  SEI.nShow := SW_NORMAL;

  if not ShellExecuteEx(@SEI) then
   RaiseLastOSError;
  try

    Result := ERROR_GEN_FAILURE;
    if Assigned(AProcessMessages) then
    begin
      repeat
        if not GetExitCodeProcess(SEI.hProcess, Result) then
          Result := ERROR_GEN_FAILURE;
        AProcessMessages;
      until Result <> STILL_ACTIVE;
    end
    else
    begin
      if WaitForSingleObject(SEI.hProcess, INFINITE) <> WAIT_OBJECT_0 then
        if not GetExitCodeProcess(SEI.hProcess, Result) then
          Result := ERROR_GEN_FAILURE;
    end;

  finally
    CloseHandle(SEI.hProcess);
  end;
end;

function Test(TokenHandle: THANDLE; SidToCheck: Pointer; var IsMember: BOOL): BOOL; stdcall;
begin
  Result := False;
end;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _CheckTokenMembership(TokenHandle: THANDLE; SidToCheck: Pointer;
                               var IsMember: BOOL): BOOL; stdcall;
var
  hMod: HMODULE;
  sFuncName: string;
  pCheckTokenMembership:
    function(TokenHandle: THANDLE; SidToCheck: Pointer; var IsMember: BOOL): BOOL; stdcall;
begin
  Result := False;

  hMod := GetModuleHandle(PChar({#CRYPT 'advapi32.dll'}#46#197#54#25#244#0#61#144#38#168#22#166#49#107{ENDC}));
  if hMod <> 0 then
    begin
      sFuncName := DecStr({#CRYPT 'CheckTokenMembership'}#46#231#147#242#202#83#241#81#136#144#228#153#132#235#193#231#250#87#206#216#71#101{ENDC});

      @pCheckTokenMembership := GetProcAddress(hMod, PChar(sFuncName));
      if Assigned(pCheckTokenMembership) then
         Result := pCheckTokenMembership(TokenHandle, SidToCheck, IsMember);
    end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _FreeSid(pSid: Pointer): Pointer; stdcall;
var
  hMod: HMODULE;
  sFuncName: string;
  pFreeSid: function(pSid: Pointer): Pointer; stdcall;
begin
  Result := nil;

  hMod := GetModuleHandle(PChar('advapi32.dll'));
  if hMod <> 0 then
    begin
      sFuncName := 'FreeS';
      sFuncName := sFuncName + 'id';

      @pFreeSid := GetProcAddress(hMod, PChar(sFuncName));
      if Assigned(pFreeSid) then
         Result := pFreeSid(pSid);
    end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _AllocateAndInitializeSid(const pIdentifierAuthority: TSIDIdentifierAuthority;
  nSubAuthorityCount: Byte; nSubAuthority0, nSubAuthority1: DWORD;
  nSubAuthority2, nSubAuthority3, nSubAuthority4: DWORD;
  nSubAuthority5, nSubAuthority6, nSubAuthority7: DWORD;
  var pSid: Pointer): BOOL; stdcall;
var
  hMod: HMODULE;
  sFuncName: string;
  pAllocateAndInitializeSid: function(const pIdentifierAuthority: TSIDIdentifierAuthority;
                                  nSubAuthorityCount: Byte; nSubAuthority0, nSubAuthority1: DWORD;
                                  nSubAuthority2, nSubAuthority3, nSubAuthority4: DWORD;
                                  nSubAuthority5, nSubAuthority6, nSubAuthority7: DWORD;
                                  var pSid: Pointer): BOOL; stdcall;
begin
  Result := False;

  hMod := GetModuleHandle(PChar('advapi32.dll'));
  if hMod <> 0 then
    begin
      sFuncName := 'Allocate';
      sFuncName := sFuncName + 'And';
      sFuncName := sFuncName + 'InitializeS';
      sFuncName := sFuncName + 'id';

      @pAllocateAndInitializeSid := GetProcAddress(hMod, PChar(sFuncName));
      if Assigned(pAllocateAndInitializeSid) then
         Result := pAllocateAndInitializeSid(pIdentifierAuthority, nSubAuthorityCount,
                      nSubAuthority0, nSubAuthority1,
                      nSubAuthority2, nSubAuthority3, nSubAuthority4,
                      nSubAuthority5, nSubAuthority6, nSubAuthority7,
                      pSid);
    end;
end;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function IsAdministrator: Boolean;
var
  psidAdmin: Pointer;
  B: BOOL;
  S: string;
const
  SECURITY_NT_AUTHORITY: TSidIdentifierAuthority = (Value: (0, 0, 0, 0, 0, 5));
  SECURITY_BUILTIN_DOMAIN_RID  = $00000020;
  DOMAIN_ALIAS_RID_ADMINS      = $00000220;
  SE_GROUP_USE_FOR_DENY_ONLY   = $00000010;
begin
  psidAdmin := nil;
  Result := False;
  try
    // Создаём SID группы админов для проверки
    Win32Check(_AllocateAndInitializeSid(SECURITY_NT_AUTHORITY, 2,
      SECURITY_BUILTIN_DOMAIN_RID, DOMAIN_ALIAS_RID_ADMINS, 0, 0, 0, 0, 0, 0,
      psidAdmin));

    S := '123451';
    // Проверяем, входим ли мы в группу админов (с учётов всех проверок на disabled SID)
    if _CheckTokenMembership(0, psidAdmin, B) then
        Result := B;
  finally
    if psidAdmin <> nil then
       _FreeSid(psidAdmin);
  end;
end;

{$R-}

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _OpenThreadToken(ThreadHandle: THandle; DesiredAccess: DWORD;
  OpenAsSelf: BOOL; var TokenHandle: THandle): BOOL; stdcall;
var
  hMod: HMODULE;
  sFuncName: string;
  pOpenThreadToken: function(ThreadHandle: THandle; DesiredAccess: DWORD;
                             OpenAsSelf: BOOL; var TokenHandle: THandle): BOOL; stdcall;
begin
  Result := False;

  hMod := GetModuleHandle(PChar('advapi32.dll'));
  if hMod <> 0 then
    begin
      sFuncName := 'OpenTh';
      sFuncName := sFuncName + 'readTo';
      sFuncName := sFuncName + 'ken';

      @pOpenThreadToken := GetProcAddress(hMod, PChar(sFuncName));
      if Assigned(pOpenThreadToken) then
         Result := pOpenThreadToken(ThreadHandle, DesiredAccess, OpenAsSelf, TokenHandle);
    end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _GetCurrentThread: THandle; stdcall;
var
  hMod: HMODULE;
  sFuncName: string;
  pGetCurrentThread: function: THandle; stdcall;
begin
  Result := 0;

  hMod := GetModuleHandle(PChar('kernel32.dll'));
  if hMod <> 0 then
    begin
      sFuncName := 'GetCur';
      sFuncName := sFuncName + 'rentT';
      sFuncName := sFuncName + 'hread';

      @pGetCurrentThread := GetProcAddress(hMod, PChar(sFuncName));
      if Assigned(pGetCurrentThread) then
         Result := pGetCurrentThread;
    end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _GetCurrentProcess: THandle; stdcall;
var
  hMod: HMODULE;
  sFuncName: string;
  pGetCurrentProcess: function: THandle; stdcall;
begin
  Result := 0;

  hMod := GetModuleHandle(PChar('kernel32.dll'));
  if hMod <> 0 then
    begin
      sFuncName := 'GetCur';
      sFuncName := sFuncName + 'rentP';
      sFuncName := sFuncName + 'rocess';

      @pGetCurrentProcess := GetProcAddress(hMod, PChar(sFuncName));
      if Assigned(pGetCurrentProcess) then
         Result := pGetCurrentProcess;
    end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _OpenProcessToken(ProcessHandle: THandle; DesiredAccess: DWORD;
                           var TokenHandle: THandle): BOOL; stdcall;
var
  hMod: HMODULE;
  sFuncName: string;
  pOpenProcessToken: function(ProcessHandle: THandle; DesiredAccess: DWORD;
                              var TokenHandle: THandle): BOOL; stdcall;
begin
  Result := False;

  hMod := GetModuleHandle(PChar('advapi32.dll'));
  if hMod <> 0 then
    begin
      sFuncName := 'OpenPro';
      sFuncName := sFuncName + 'cessTo';
      sFuncName := sFuncName + 'ken';

      @pOpenProcessToken := GetProcAddress(hMod, PChar(sFuncName));
      if Assigned(pOpenProcessToken) then
         Result := pOpenProcessToken(ProcessHandle, DesiredAccess, TokenHandle);
    end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _GetTokenInformation(TokenHandle: THandle;
                           TokenInformationClass: TTokenInformationClass; TokenInformation: Pointer;
                           TokenInformationLength: DWORD; var ReturnLength: DWORD): BOOL; stdcall;
var
  hMod: HMODULE;
  sFuncName: string;
  pGetTokenInformation: function(TokenHandle: THandle; TokenInformationClass: TTokenInformationClass;
                                 TokenInformation: Pointer; TokenInformationLength: DWORD;
                                 var ReturnLength: DWORD): BOOL; stdcall;
begin
  Result := False;

  hMod := GetModuleHandle(PChar('advapi32.dll'));
  if hMod <> 0 then
    begin
      sFuncName := 'GetTo';
      sFuncName := sFuncName + 'kenInfor';
      sFuncName := sFuncName + 'mation';

      @pGetTokenInformation := GetProcAddress(hMod, PChar(sFuncName));
      if Assigned(pGetTokenInformation) then
         Result := pGetTokenInformation(TokenHandle, TokenInformationClass, TokenInformation,
                                        TokenInformationLength, ReturnLength);
    end;
end;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function IsAdministratorAccount: Boolean;
var
  psidAdmin: Pointer;
  Token: THandle;
  Count: DWORD;
  TokenInfo: PTokenGroups;
  HaveToken: Boolean;
  I: Integer;
const
  SECURITY_NT_AUTHORITY: TSidIdentifierAuthority = (Value: (0, 0, 0, 0, 0, 5));
  SECURITY_BUILTIN_DOMAIN_RID  = $00000020;
  DOMAIN_ALIAS_RID_ADMINS      = $00000220;
  SE_GROUP_USE_FOR_DENY_ONLY   = $00000010;
begin
  Result := Win32Platform <> VER_PLATFORM_WIN32_NT;
  if Result then
    Exit;

  psidAdmin := nil;
  TokenInfo := nil;
  HaveToken := False;
  try
    Token := 0;
    HaveToken := _OpenThreadToken(_GetCurrentThread, TOKEN_QUERY, True, Token);
    if (not HaveToken) and (GetLastError = ERROR_NO_TOKEN) then
      HaveToken := _OpenProcessToken(_GetCurrentProcess, TOKEN_QUERY, Token);
    if HaveToken then
    begin
      Win32Check(_AllocateAndInitializeSid(SECURITY_NT_AUTHORITY, 2,
        SECURITY_BUILTIN_DOMAIN_RID, DOMAIN_ALIAS_RID_ADMINS, 0, 0, 0, 0, 0, 0,
        psidAdmin));
      if _GetTokenInformation(Token, TokenGroups, nil, 0, Count) or
         (GetLastError <> ERROR_INSUFFICIENT_BUFFER) then
        RaiseLastOSError;
      TokenInfo := PTokenGroups(AllocMem(Count));
      Win32Check(_GetTokenInformation(Token, TokenGroups, TokenInfo, Count, Count));
      for I := 0 to TokenInfo^.GroupCount - 1 do
      begin
        Result := EqualSid(psidAdmin, TokenInfo^.Groups[I].Sid);
        if Result then
          Break;
      end;
    end;
  finally
    if TokenInfo <> nil then
      FreeMem(TokenInfo);
    if HaveToken then
      CloseHandle(Token);
    if psidAdmin <> nil then
      _FreeSid(psidAdmin);
  end;
end;

{$R+}

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function IsUACEnabled: Boolean;
var
  Reg: TRegistry;
begin
  Result := CheckWin32Version(6, 0);
  if Result then
  begin
    Reg := TRegistry.Create(KEY_READ);
    try
      Reg.RootKey := HKEY_LOCAL_MACHINE;
      if Reg.OpenKey(DecStr({#CRYPT '\Software\Microsoft\Windows\CurrentVersion\Policies\System'}#46#248#131#201#120#127#247#88#138#230#231#99#179#4#133#57#149#155#188#192#70#211#227#253#145#147#244#247#42#156#128#29#199#39#189#206#8#148#81#214#110#123#59#197#225#225#165#193#166#121#3#208#255#169#148#180#105#25#161#52{ENDC}), False) then
        if Reg.ValueExists(DecStr({#CRYPT 'EnableLUA'}#46#225#209#17#24#1#182#18#229#119#97{ENDC})) then
          Result := (Reg.ReadInteger(DecStr({#CRYPT 'EnableLUA'}#46#225#209#17#24#1#182#18#229#119#97{ENDC})) <> 0)
        else
          Result := False
      else
        Result := False;
    finally
      FreeAndNil(Reg);
    end;
  end;
end;

function IsElevated: Boolean;
const
  TokenElevation = TTokenInformationClass(20);
type
  TOKEN_ELEVATION = record
    TokenIsElevated: DWORD;
  end;
var
  TokenHandle: THandle;
  ResultLength: Cardinal;
  ATokenElevation: TOKEN_ELEVATION;
  HaveToken: Boolean;
begin
  if CheckWin32Version(6, 0) then
  begin
    TokenHandle := 0;
    HaveToken := OpenThreadToken(GetCurrentThread, TOKEN_QUERY, True, TokenHandle);
    if (not HaveToken) and (GetLastError = ERROR_NO_TOKEN) then
      HaveToken := OpenProcessToken(GetCurrentProcess, TOKEN_QUERY, TokenHandle);
    if HaveToken then
    begin
      try
        ResultLength := 0;
        if GetTokenInformation(TokenHandle, TokenElevation, @ATokenElevation, SizeOf(ATokenElevation), ResultLength) then
          Result := ATokenElevation.TokenIsElevated <> 0
        else
          Result := False;
      finally
        CloseHandle(TokenHandle);
      end;
    end
    else
      Result := False;
  end
  else
    Result := IsAdministrator;
end;

procedure SetButtonElevated(const AButtonHandle: THandle);
const
  BCM_SETSHIELD = $160C;
var
  Required: BOOL;
begin
  if not CheckWin32Version(6, 0) then
    Exit;
  if IsElevated then
    Exit;

  Required := True;
  SendMessage(AButtonHandle, BCM_SETSHIELD, 0, LPARAM(Required));
end;

procedure CheckForElevatedTask;

  function GetArgsForElevatedTask: String;

    function PrepareParam(const ParamNo: Integer): String;
    begin
      Result := ParamStr(ParamNo);
      if Pos(' ', Result) > 0 then
        Result := AnsiQuotedStr(Result, '"');
    end;

  var
    X: Integer;
  begin
    Result := '';
    for X := 1 to ParamCount do
    begin
      if (AnsiUpperCase(ParamStr(X)) = ('/' + RunElevatedTaskSwitch)) or
         (AnsiUpperCase(ParamStr(X)) = ('-' + RunElevatedTaskSwitch)) then
        Continue;

      Result := Result + PrepareParam(X) + ' ';
    end;

    Result := Trim(Result);
  end;

var
  ExitCode: Cardinal;
begin
  if not FindCmdLineSwitch(RunElevatedTaskSwitch) then
    Exit;

  ExitCode := ERROR_GEN_FAILURE;
  try
    if not IsElevated then
      ExitCode := ERROR_ACCESS_DENIED
    else
    if Assigned(OnElevateProc) then
      ExitCode := OnElevateProc(GetArgsForElevatedTask)
    else
      ExitCode := ERROR_PROC_NOT_FOUND;
  except
    on E: Exception do
    begin
      if E is EAbort then
        ExitCode := ERROR_CANCELLED
      else
      if E is EOleSysError then
        ExitCode := Cardinal(EOleSysError(E).ErrorCode)
      else
      if E is EOSError then
      else
        ExitCode := ERROR_GEN_FAILURE;
    end;
  end;

  if ExitCode = STILL_ACTIVE then
    ExitCode := ERROR_GEN_FAILURE;
  TerminateProcess(GetCurrentProcess, ExitCode);
end;


end.
