import {
  BrowserMultiFormatReader,
  DecodeHintType,
  BarcodeFormat,
  Result,
  Exception
} from '@zxing/library';

export class QRScanner {
  private reader: BrowserMultiFormatReader;
  private currentControls: { stop: () => void } | null = null;
  private paused = false;

  constructor() {
    const hints = new Map();
    hints.set(DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.QR_CODE]);
    hints.set(DecodeHintType.TRY_HARDER, true);
    this.reader = new BrowserMultiFormatReader(hints, 200);
  }

  async listVideoDevices(): Promise<MediaDeviceInfo[]> {
    return await this.reader.listVideoInputDevices();
  }

  /**
   * Empieza a escanear sobre el <video>.
   * `onDecode` se llama una vez por QR detectado (mientras no este pausado).
   */
  async start(
    videoEl: HTMLVideoElement,
    onDecode: (text: string) => void,
    onError?: (err: Error) => void,
    deviceId?: string
  ): Promise<void> {
    this.stop();
    this.paused = false;

    // Si no hay deviceId, preferimos cámara trasera.
    let selectedDeviceId = deviceId;
    if (!selectedDeviceId) {
      try {
        const devices = await this.listVideoDevices();
        const back =
          devices.find((d) => /back|rear|trasera|environment/i.test(d.label)) ||
          devices[devices.length - 1];
        selectedDeviceId = back?.deviceId;
      } catch {
        selectedDeviceId = undefined;
      }
    }

    try {
      await this.reader.decodeFromVideoDevice(
        selectedDeviceId || null,
        videoEl,
        (result: Result | undefined, err: Exception | undefined) => {
          if (this.paused) return;
          if (result) {
            onDecode(result.getText());
          } else if (err && err.name !== 'NotFoundException' && onError) {
            onError(err as unknown as Error);
          }
        }
      );
    } catch (e) {
      if (onError) onError(e as Error);
      throw e;
    }
  }

  pause() {
    this.paused = true;
  }

  resume() {
    this.paused = false;
  }

  stop() {
    try {
      this.reader.reset();
    } catch {
      // ignore
    }
    this.currentControls = null;
  }
}

/**
 * Extrae el token de una URL tipo https://.../v/TOKEN.
 * Si no parece una URL, asume que el texto en si es el token.
 */
export function extractToken(raw: string): string {
  if (!raw) return '';
  const trimmed = raw.trim();
  try {
    const url = new URL(trimmed);
    const parts = url.pathname.split('/').filter(Boolean);
    if (parts.length > 0) {
      return parts[parts.length - 1];
    }
  } catch {
    // not a URL, return as-is
  }
  return trimmed;
}
