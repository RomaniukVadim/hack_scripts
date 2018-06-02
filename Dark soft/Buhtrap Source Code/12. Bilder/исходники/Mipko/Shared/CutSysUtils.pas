unit CutSysUtils;

interface

uses
  Windows;

type
{ MultiByte Character Set (MBCS) byte type }
  TMbcsByteType = (mbSingleByte, mbLeadByte, mbTrailByte);

{ Generic filename type }
  TFileName = type string;

{ Search record used by FindFirst, FindNext, and FindClose }

  TSearchRec = record
    Time: Integer;
    Size: Int64;
    Attr: Integer;
    Name: TFileName;
    ExcludeAttr: Integer;
{$IFDEF MSWINDOWS}
    FindHandle: THandle  platform;
    FindData: TWin32FindData  platform;
{$ENDIF}
{$IFDEF LINUX}
    Mode: mode_t  platform;
    FindHandle: Pointer  platform;
    PathOnly: String  platform;
    Pattern: String  platform;
{$ENDIF}
  end;

{ Type conversion records }

  WordRec = packed record
    case Integer of
      0: (Lo, Hi: Byte);
      1: (Bytes: array [0..1] of Byte);
  end;

  LongRec = packed record
    case Integer of
      0: (Lo, Hi: Word);
      1: (Words: array [0..1] of Word);
      2: (Bytes: array [0..3] of Byte);
  end;

  Int64Rec = packed record
    case Integer of
      0: (Lo, Hi: Cardinal);
      1: (Cardinals: array [0..1] of Cardinal);
      2: (Words: array [0..3] of Word);
      3: (Bytes: array [0..7] of Byte);
  end;

const
  PathDelim  = {$IFDEF MSWINDOWS} '\'; {$ELSE} '/'; {$ENDIF}
  DriveDelim = {$IFDEF MSWINDOWS} ':'; {$ELSE} '';  {$ENDIF}
  PathSep    = {$IFDEF MSWINDOWS} ';'; {$ELSE} ':'; {$ENDIF}

{ File attribute constants }

  faReadOnly  = $00000001 platform;
  faHidden    = $00000002 platform;
  faSysFile   = $00000004 platform;
  faVolumeID  = $00000008 platform deprecated;  // not used in Win32
  faDirectory = $00000010;
  faArchive   = $00000020 platform;
  faSymLink   = $00000040 platform;
  faAnyFile   = $0000003F;


{ LowerCase converts all ASCII characters in the given string to lower case.
  The conversion affects only 7-bit ASCII characters between 'A' and 'Z'. To
  convert 8-bit international characters, use AnsiLowerCase. }
function LowerCase(const S: string): string;

{ ExtractFileName extracts the name and extension parts of the given
  filename. The resulting string is the leftmost characters of FileName,
  starting with the first character after the colon or backslash that
  separates the path information from the name and extension. The resulting
  string is equal to FileName if FileName contains no drive and directory
  parts. }
function ExtractFileName(const FileName: string): string;

{ ExtractFilePath extracts the drive and directory parts of the given
  filename. The resulting string is the leftmost characters of FileName,
  up to and including the colon or backslash that separates the path
  information from the name and extension. The resulting string is empty
  if FileName contains no drive and directory parts. }
function ExtractFilePath(const FileName: string): string;

{ LastDelimiter returns the byte index in S of the rightmost whole
  character that matches any character in Delimiters (except null (#0)).
  S may contain multibyte characters; Delimiters must contain only single
  byte non-null characters.
  Example: LastDelimiter('\.:', 'c:\filename.ext') returns 12. }
function LastDelimiter(const Delimiters, S: string): Integer;

{ StrScan returns a pointer to the first occurrence of Chr in Str. If Chr
  does not occur in Str, StrScan returns NIL. The null terminator is
  considered to be part of the string. }
function StrScan(const Str: PChar; Chr: Char): PChar;

{ ByteType indicates what kind of byte exists at the Index'th byte in S.
  Western locales always return mbSingleByte.  Far East multibyte locales
  may also return mbLeadByte, indicating the byte is the first in a multibyte
  character sequence, and mbTrailByte, indicating that the byte is one of
  a sequence of bytes following a lead byte.  One or more trail bytes can
  follow a lead byte, depending on locale charset encoding and OS platform.
  Parameters are assumed to be valid. }
function ByteType(const S: string; Index: Integer): TMbcsByteType;

{ StrPas converts Str to a Pascal style string. This function is provided
  for backwards compatibility only. To convert a null terminated string to
  a Pascal style string, use a string type cast or an assignment. }
function StrPas(const Str: PChar): string;

{ FileExists returns a boolean value that indicates whether the specified
  file exists. }
function FileExists(const FileName: string): Boolean;

{ AddCharR return a string right-padded to length N with characters C. }
function AddCharR(C: Char; const S: string; N: Integer): string;

{ MakeStr return a string of length N filled with character C. }
function MakeStr(C: Char; N: Integer): string;

function ExtractQuotedString(const S: string; Quote: Char): string;
{ ExtractQuotedString removes the Quote characters from the beginning and
  end of a quoted string, and reduces pairs of Quote characters within
  the quoted string to a single character. }

{ IntToStr converts the given value to its decimal string representation. }
function IntToStr(Value: Integer): string; overload;

function QuotedString(const S: string; Quote: Char): string;
{ QuotedString returns the given string as a quoted string, using the
  provided Quote character. }

function IsEqualGUID(const guid1, guid2: TGUID): Boolean;
{$IFDEF MSWINDOWS}
  stdcall;  {$EXTERNALSYM IsEqualGUID}
{$ENDIF}

{ FindFirst searches the directory given by Path for the first entry that
  matches the filename given by Path and the attributes given by Attr. The
  result is returned in the search record given by SearchRec. The return
  value is zero if the function was successful. Otherwise the return value
  is a system error code. After calling FindFirst, always call FindClose.
  FindFirst is typically used with FindNext and FindClose as follows:

    Result := FindFirst(Path, Attr, SearchRec);
    while Result = 0 do
    begin
      ProcessSearchRec(SearchRec);
      Result := FindNext(SearchRec);
    end;
    FindClose(SearchRec);

  where ProcessSearchRec represents user-defined code that processes the
  information in a search record. }

function FindFirst(const Path: string; Attr: Integer;
  var F: TSearchRec): Integer;

{ FindNext returs the next entry that matches the name and attributes
  specified in a previous call to FindFirst. The search record must be one
  that was passed to FindFirst. The return value is zero if the function was
  successful. Otherwise the return value is a system error code. }

function FindNext(var F: TSearchRec): Integer;

{ FindClose terminates a FindFirst/FindNext sequence and frees memory and system
  resources allocated by FindFirst.
  Every FindFirst/FindNext must end with a call to FindClose. }

procedure FindClose(var F: TSearchRec);

{ FileGetDate returns the OS date-and-time stamp of the file given by
  Handle. The return value is -1 if the handle is invalid. The
  FileDateToDateTime function can be used to convert the returned value to
  a TDateTime value. }



implementation

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function LowerCase(const S: string): string;
var
  Ch: Char;
  L: Integer;
  Source, Dest: PChar;
begin
  L := Length(S);
  SetLength(Result, L);
  Source := Pointer(S);
  Dest := Pointer(Result);
  while L <> 0 do
  begin
    Ch := Source^;
    if (Ch >= 'A') and (Ch <= 'Z') then Inc(Ch, 32);
    Dest^ := Ch;
    Inc(Source);
    Inc(Dest);
    Dec(L);
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function StrScan(const Str: PChar; Chr: Char): PChar; assembler;
asm
        PUSH    EDI
        PUSH    EAX
        MOV     EDI,Str
        MOV     ECX,$FFFFFFFF
        XOR     AL,AL
        REPNE   SCASB
        NOT     ECX
        POP     EDI
        MOV     AL,Chr
        REPNE   SCASB
        MOV     EAX,0
        JNE     @@1
        MOV     EAX,EDI
        DEC     EAX
@@1:    POP     EDI
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function LastDelimiter(const Delimiters, S: string): Integer;
var
  P: PChar;
begin
  Result := Length(S);
  P := PChar(Delimiters);
  while Result > 0 do
  begin
    if (S[Result] <> #0) and (StrScan(P, S[Result]) <> nil) then
{$IFDEF MSWINDOWS}
      if (ByteType(S, Result) = mbTrailByte) then
        Dec(Result)
      else
        Exit;
{$ENDIF}
    Dec(Result);
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function ExtractFileName(const FileName: string): string;
var
  I: Integer;
begin
  I := LastDelimiter(PathDelim + DriveDelim, FileName);
  Result := Copy(FileName, I + 1, MaxInt);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function ExtractFilePath(const FileName: string): string;
var
  I: Integer;
begin
  I := LastDelimiter(PathDelim + DriveDelim, FileName);
  Result := Copy(FileName, 1, I);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function ByteType(const S: string; Index: Integer): TMbcsByteType;
begin
  Result := mbSingleByte;
//  if SysLocale.FarEast then
//    Result := ByteTypeTest(PChar(S), Index-1);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function StrPas(const Str: PChar): string;
begin
  Result := Str;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function FileExists(const FileName: string): Boolean;
{$IFDEF MSWINDOWS}
  function ExistsLockedOrShared(const Filename: string): Boolean;
  var
    FindData: TWin32FindData;
    LHandle: THandle;
  begin
    { Either the file is locked/share_exclusive or we got an access denied }
    LHandle := FindFirstFile(PChar(Filename), FindData);
    if LHandle <> INVALID_HANDLE_VALUE then
    begin
      Windows.FindClose(LHandle);
      Result := FindData.dwFileAttributes and FILE_ATTRIBUTE_DIRECTORY = 0;
    end
    else
      Result := False;
  end;

var
  Code: Integer;
  LastError: Cardinal;
begin
  Code := GetFileAttributes(PChar(FileName));
  if Code <> -1 then
    Result := (FILE_ATTRIBUTE_DIRECTORY and Code = 0)
  else
  begin
    LastError := GetLastError;
    Result := (LastError <> ERROR_FILE_NOT_FOUND) and
      (LastError <> ERROR_PATH_NOT_FOUND) and
      (LastError <> ERROR_INVALID_NAME) and ExistsLockedOrShared(Filename);
  end;
end;
{$ENDIF}
{$IFDEF LINUX}
begin
  Result := euidaccess(PChar(FileName), F_OK) = 0;
end;
{$ENDIF}

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function AddCharR(C: Char; const S: string; N: Integer): string;
begin
  if Length(S) < N then
    Result := S + MakeStr(C, N - Length(S))
  else Result := S;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function MakeStr(C: Char; N: Integer): string;
begin
  if N < 1 then Result := ''
  else begin
{$IFNDEF WIN32}
    if N > 255 then N := 255;
{$ENDIF WIN32}
    SetLength(Result, N);
    FillChar(Result[1], Length(Result), C);
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function ExtractQuotedString(const S: string; Quote: Char): string;
var
{$IFDEF MBCS}
  P: PChar;
begin
  P := PChar(S);
  if P^ = Quote then Result := AnsiExtractQuotedStr(P, Quote)
  else Result := S;
{$ELSE}
  I: Integer;
begin
  Result := S;
  I := Length(Result);
  if (I > 0) and (Result[1] = Quote) and
    (Result[I] = Quote) then
  begin
    Delete(Result, I, 1);
    Delete(Result, 1, 1);
    for I := Length(Result) downto 2 do begin
      if (Result[I] = Quote) and (Result[I - 1] = Quote) then
        Delete(Result, I, 1);
    end;
  end;
{$ENDIF MBCS}
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure CvtInt;
{ IN:
    EAX:  The integer value to be converted to text
    ESI:  Ptr to the right-hand side of the output buffer:  LEA ESI, StrBuf[16]
    ECX:  Base for conversion: 0 for signed decimal, 10 or 16 for unsigned
    EDX:  Precision: zero padded minimum field width
  OUT:
    ESI:  Ptr to start of converted text (not start of buffer)
    ECX:  Length of converted text
}
asm
        OR      CL,CL
        JNZ     @CvtLoop
@C1:    OR      EAX,EAX
        JNS     @C2
        NEG     EAX
        CALL    @C2
        MOV     AL,'-'
        INC     ECX
        DEC     ESI
        MOV     [ESI],AL
        RET
@C2:    MOV     ECX,10

@CvtLoop:
        PUSH    EDX
        PUSH    ESI
@D1:    XOR     EDX,EDX
        DIV     ECX
        DEC     ESI
        ADD     DL,'0'
        CMP     DL,'0'+10
        JB      @D2
        ADD     DL,('A'-'0')-10
@D2:    MOV     [ESI],DL
        OR      EAX,EAX
        JNE     @D1
        POP     ECX
        POP     EDX
        SUB     ECX,ESI
        SUB     EDX,ECX
        JBE     @D5
        ADD     ECX,EDX
        MOV     AL,'0'
        SUB     ESI,EDX
        JMP     @z
@zloop: MOV     [ESI+EDX],AL
@z:     DEC     EDX
        JNZ     @zloop
        MOV     [ESI],AL
@D5:
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function IntToStr(Value: Integer): string;
//  FmtStr(Result, '%d', [Value]);
asm
        PUSH    ESI
        MOV     ESI, ESP
        SUB     ESP, 16
        XOR     ECX, ECX       // base: 0 for signed decimal
        PUSH    EDX            // result ptr
        XOR     EDX, EDX       // zero filled field width: 0 for no leading zeros
        CALL    CvtInt
        MOV     EDX, ESI
        POP     EAX            // result ptr
        CALL    System.@LStrFromPCharLen
        ADD     ESP, 16
        POP     ESI
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function QuotedString(const S: string; Quote: Char): string;
{$IFDEF MBCS}
begin
  Result := AnsiQuotedStr(S, Quote);
{$ELSE}
var
  I: Integer;
begin
  Result := S;
  for I := Length(Result) downto 1 do
    if Result[I] = Quote then Insert(Quote, Result, I);
  Result := Quote + Result + Quote;
{$ENDIF MBCS}
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function IsEqualGUID; external 'ole32.dll' name 'IsEqualGUID';

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function FindMatchingFile(var F: TSearchRec): Integer;
{$IFDEF MSWINDOWS}
var
  LocalFileTime: TFileTime;
begin
  with F do
  begin
    while FindData.dwFileAttributes and ExcludeAttr <> 0 do
      if not FindNextFile(FindHandle, FindData) then
      begin
        Result := GetLastError;
        Exit;
      end;
    FileTimeToLocalFileTime(FindData.ftLastWriteTime, LocalFileTime);
    FileTimeToDosDateTime(LocalFileTime, LongRec(Time).Hi,
      LongRec(Time).Lo);
    Size := FindData.nFileSizeLow or Int64(FindData.nFileSizeHigh) shl 32;
    Attr := FindData.dwFileAttributes;
    Name := FindData.cFileName;
  end;
  Result := 0;
end;
{$ENDIF}

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function FindFirst(const Path: string; Attr: Integer;
  var  F: TSearchRec): Integer;
const
  faSpecial = faHidden or faSysFile or faDirectory;
{$IFDEF MSWINDOWS}
begin
  F.ExcludeAttr := not Attr and faSpecial;
  F.FindHandle := FindFirstFile(PChar(Path), F.FindData);
  if F.FindHandle <> INVALID_HANDLE_VALUE then
  begin
    Result := FindMatchingFile(F);
    if Result <> 0 then FindClose(F);
  end else
    Result := GetLastError;
end;
{$ENDIF}
{$IFDEF LINUX}
begin
  F.ExcludeAttr := not Attr and faSpecial;
  F.PathOnly := ExtractFilePath(Path);
  F.Pattern := ExtractFileName(Path);
  if F.PathOnly = '' then
    F.PathOnly := IncludeTrailingPathDelimiter(GetCurrentDir);

  F.FindHandle := opendir(PChar(F.PathOnly));
  if F.FindHandle <> nil then
  begin
    Result := FindMatchingFile(F);
    if Result <> 0 then
      FindClose(F);
  end
  else
    Result:= GetLastError;
end;
{$ENDIF}

function FindNext(var F: TSearchRec): Integer;
begin
{$IFDEF MSWINDOWS}
  if FindNextFile(F.FindHandle, F.FindData) then
    Result := FindMatchingFile(F) else
    Result := GetLastError;
{$ENDIF}
{$IFDEF LINUX}
  Result := FindMatchingFile(F);
{$ENDIF}
end;

procedure FindClose(var F: TSearchRec);
begin
{$IFDEF MSWINDOWS}
  if F.FindHandle <> INVALID_HANDLE_VALUE then
  begin
    Windows.FindClose(F.FindHandle);
    F.FindHandle := INVALID_HANDLE_VALUE;
  end;
{$ENDIF}
{$IFDEF LINUX}
  if F.FindHandle <> nil then
  begin
    closedir(F.FindHandle);
    F.FindHandle := nil;
  end;
{$ENDIF}
end;


end.
