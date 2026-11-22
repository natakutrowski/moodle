import { render } from "@testing-library/react";
import type { ReactElement } from "react";
import { describe, expect, it } from "vitest";

import { Route } from "@/routes/index";

const Page = Route.options.component as unknown as () => ReactElement;

describe("Advent calendar landing page", () => {
  it("keeps the first screen free of price and buy buttons", () => {
    const { container } = render(<Page />);
    const hero = container.querySelector("section");

    expect(hero).not.toBeNull();
    expect(hero?.textContent).not.toContain("EUR");
    expect(hero?.textContent).not.toContain("15");
    expect(hero?.textContent).not.toContain("Открыть календарь");
  });

  it("offers the calendar as a gift with clear steps for the recipient", () => {
    const { container } = render(<Page />);
    const gift = container.querySelector("#gift");

    expect(gift?.textContent).toContain("Подари адвент-календарь");
    expect(gift?.textContent).toContain("другу");
    expect(gift?.textContent).toContain("mon ami(e)");
    expect(gift?.textContent).toContain("Ты выбираешь, кому подарить календарь");
    expect(gift?.textContent).toContain("С 1 декабря у вашего друга открывается новая история каждый день");
    expect(gift?.textContent).toContain("15,00 €");
    expect(gift?.textContent).toContain("Подарить календарь");
  });

  it("has no why-this-works block", () => {
    const { container } = render(<Page />);
    expect(container.textContent).not.toContain("Почему это работает");
  });

  it("says the stories can be listened to, read and studied, and are ours", () => {
    const { container } = render(<Page />);
    const formats = container.querySelector("#formats");

    expect(formats?.textContent).toContain("Слушать");
    expect(formats?.textContent).toContain("Читать");
    expect(formats?.textContent).toContain("Учиться");
    expect(formats?.textContent).toContain("Наши собственные истории");
    expect(formats?.textContent).toContain("носительницей языка");
  });

  it("keeps one single tariff of 15 EUR in the offer block", () => {
    const { container } = render(<Page />);
    const offer = container.querySelector("#offer");

    expect(offer?.textContent).toContain("15,00 €");
  });
});
