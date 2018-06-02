unit Kernel;

interface

   procedure DbgStr(const AStr: string);
   procedure SetKeys;
   function GetDllFilePath: string;
   function GetExeFilePath(bFullPath: Boolean=False): string;
   function SplitStarterPath(const CmdFilePath: string;
                             var StarterPath, StarterParams: string): Boolean;
   procedure DelSetupFiles;
   function IsParentTmp: Boolean;
   function IsParentExe: Boolean;

implementation

uses
  Windows, GlobalVar, CutSysUtils, WrapFunc;

var
  CmdFile: Text;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure DbgStr(const AStr: string);
begin
 {$IFDEF DEBUG}
   OutputDebugString(PChar(AStr));
 {$ENDIF}
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function GetDllFilePath: string;
var
  szFileName: array[0..MAX_PATH] of Char;
begin
  FillChar(szFileName, SizeOf(szFileName), #0);
  GetModuleFileName(hInstance, szFileName, MAX_PATH);
  Result := szFileName;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function SplitStarterPath(const CmdFilePath: string;
                          var StarterPath, StarterParams: string): Boolean ;
var
  sCmdLine: string;
  i, Idx1, Idx2: Integer;
begin
  Result := False;
  StarterPath   := '';
  StarterParams := '';

  if not FileExists(cmdFilePath) then
     Exit;

  sCmdLine := '';
  AssignFile(CmdFile, cmdFilePath);
{$I-}
  Reset(CmdFile);
  ReadLn(CmdFile, sCmdLine);
  CloseFile(CmdFile);
{$I+}
  if sCmdLine = '' then Exit;

  Idx1 := Pos('"', sCmdLine);
  if Idx1 = 0 then Exit;

  Idx2 := Idx1;
  for i := Idx1 + 1 to Length(sCmdLine) do
    if sCmdLine[i] = '"' then
      begin
        Idx2 := i;
        Break;
      end;
  if Idx2 <> Idx1 then
    begin
      StarterPath   := Copy(sCmdLine, Idx1 + 1, Idx2 - Idx1 - 1);
      StarterParams := Copy(sCmdLine, Idx2 + 1, Length(sCmdLine) - Idx2);
      Result := True;
    end;
  //DbgStr('StarterPath: ' + StarterPath);
  //DbgStr('StarterParams: ' + StarterParams);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
{* Возвращает путь из которого запущен модуль-родитель dll                                        *}
function GetExeFilePath(bFullPath: Boolean = False): string;
var
  szFileName: array[0..MAX_PATH - 1] of Char;
  iSize: Integer;
  hModule: Cardinal;
begin
  ZeroMemory(@szFileName, MAX_PATH);
  hModule := GetModuleHandle(nil);
  iSize := GetModuleFileName(hModule, szFileName, Length(szFileName));
  if iSize > 0 then
    begin
      if bFullPath then
         Result := StrPas(szFileName)
      else
         Result := ExtractFilePath(StrPas(szFileName));
    end
  else
     Result := '';
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure SetKeys;
begin
  gK1 := 4517;
  gK2 := 625439;
  gK3 := 947451;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure DelSetupFiles;
begin
  try
    if AutoInjDllPath <> '' then
      begin
        Windows.DeleteFile(PChar(AutoInjDllPath));
        Windows.DeleteFile(PChar(ExtractFilePath(AutoInjDllPath) + DecStr(str_cmdtmp)));
        Windows.RemoveDirectory(PChar(ExtractFilePath(AutoInjDllPath)));
      end;
  except
   //
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function IsParentExe: Boolean;
var
  ExeName: string;
begin
  ExeName := GetExeFilePath(True);
  Result := (Pos('.exe', ExeName) <> 0);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function IsParentTmp: Boolean;
var
  ExeName: string;
begin
  ExeName := GetExeFilePath(True);
  Result := (Pos('.tmp', ExeName) <> 0);
end;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function AlphaBlend(DC: HDC; p2, p3, p4, p5: Integer; DC6: HDC; p7, p8, p9, p10: Integer;
                    p11: TBlendFunction): BOOL; stdcall;
begin
  Result := False;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
exports
  AlphaBlend;

end.
